<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\LDraw\ZipFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(ZipFiles $zipFiles): void
    {
        $r = PartRelease::firstWhere('short', '2603');
        $zipFiles->releaseZips($r, [], 'library/official/models/Note2603CA.txt', false, Storage::path('release-staging'));
        $releaseViewPath = "library-media/part_releases/{$r->id}";
        foreach(Storage::files($releaseViewPath) as $file) {
            $partName = basename($file, '.png');
            $part = Part::official()->where('filename', "parts/{$partName}.dat")->first();
            $officialImagePath = $part->getFirstMediaPath('image');
            $imageStoragePath = Str::chopStart($officialImagePath, storage_path(). '/app/');
            Storage::copy($imageStoragePath, $file);
        }
    }
}
