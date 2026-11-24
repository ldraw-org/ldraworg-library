<?php

namespace App\Services\Part\Release;

use App\Jobs\GenerateReleaseImage;
use App\Models\Part\PartRelease;
use App\Services\Support\ZipFiles;
use Illuminate\Support\Facades\Storage;

class ReleaseArchiver
{
    public function __construct(
        protected ZipFiles $zipFiles,
    ) {
    }

    /**
     * Archive the previous release's downloads, build and publish this release's
     * zips, notes and extra files, then enable the release.
     *
     * @param array<string, string> $extraFiles file contents keyed by library-relative filename
     */
    public function publish(
        PartRelease $release,
        string $stagingFolder,
        string $notesPath,
        array $extraFiles,
        bool $includeLdconfig,
    ): void {
        $previousRelease = PartRelease::where('id', '<>', $release->id)->latest()->first();

        Storage::move('library/updates/complete.zip', "library/updates/complete-{$previousRelease->short}.zip");
        Storage::move('library/updates/LDrawParts.exe', "library/updates/LDraw{$previousRelease->short}.exe");

        $this->zipFiles->releaseZips(
            $release,
            $extraFiles,
            $notesPath,
            $includeLdconfig,
            Storage::path($stagingFolder)
        );
        Storage::move("{$stagingFolder}/lcad{$release->short}.zip", "library/updates/lcad{$release->short}.zip");
        Storage::move("{$stagingFolder}/complete.zip", 'library/updates/complete.zip');

        Storage::move($notesPath, "library/official/models/Note{$release->short}CA.txt");

        foreach ($extraFiles as $filename => $contents) {
            Storage::put("library/official/ldraw/{$filename}", $contents);
        }

        GenerateReleaseImage::dispatch($release);

        $release->enabled = true;
        $release->save();
    }
}
