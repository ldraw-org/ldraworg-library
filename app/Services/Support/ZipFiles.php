<?php

namespace App\Services\Support;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

class ZipFiles
{
    public function partZip(Part $part): ?string
    {
        if (! $part->type->inPartsFolder()) {
            return null;
        }

        $scratch = $this->newScratch();
        $name = basename($part->filename, '.dat').'.zip';
        $zipPath = $scratch->path($name);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $query = $part->isUnofficial()
            ? $part->descendantsAndSelf()->doesntHave('unofficial_part')
            : $part->descendantsAndSelf()->official();

        foreach ($query->cursor() as $zipPart) {
            $this->addPartToZip($zip, $zipPart, $scratch);
        }

        $zip->close();
        $file = file_get_contents($zipPath);
        $scratch->delete();

        return $file ?: '';
    }

    public function unofficialZip(?Part $part = null, ?string $oldfilename = null): void
    {
        $scratch = $this->newScratch();
        $zip = new ZipArchive;

        if (Storage::exists('library/unofficial/ldrawunf.zip')) {
            $zip->open(Storage::path('library/unofficial/ldrawunf.zip'));
            if (! is_null($oldfilename)) {
                $zip->deleteName($oldfilename);
            }
            if (! is_null($part)) {
                $this->addPartToZip($zip, $part, $scratch);
            }
        } else {
            Storage::put('library/unofficial/ldrawunf.zip', '');
            $zip->open(Storage::path('library/unofficial/ldrawunf.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile(Storage::path('library/official/CAreadme.txt'), 'CAreadme.txt');
            $zip->addFile(Storage::path('library/official/CAlicense.txt'), 'CAlicense.txt');
            $zip->addFile(Storage::path('library/official/CAlicense4.txt'), 'CAlicense4.txt');

            Part::with('body')
                ->select(['id', 'created_at', 'type', 'filename', 'header'])
                ->unofficial()
                ->orderBy('filename')
                ->chunk(
                    500,
                    function (Collection $parts) use ($zip, $scratch) {
                        $parts->each(fn (Part $part) => $this->addPartToZip($zip, $part, $scratch));
                    }
                );
        }

        $zip->close();
        $scratch->delete();
    }

    public function releaseZips(PartRelease $release, array $extraFiles, string $notes, bool $includeLDConfig, string $path): void
    {
        $root = 'ldraw/';
        $scratch = $this->newScratch();

        $updateZip = new ZipArchive;
        $updateZip->open("{$path}/lcad{$release->short}.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $completeZip = new ZipArchive;
        $completeZip->open("{$path}/complete.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (Storage::allFiles('library/official') as $file) {
            $filename = Str::replaceFirst('library/official/', $root, $file);
            $diskPath = Storage::path($file);

            $completeZip->addFile($diskPath, $filename);

            if (
                $includeLDConfig &&
                in_array($file, [
                    'library/official/LDConfig.ldr',
                    'library/official/LDCfgalt.ldr',
                ])
            ) {
                $updateZip->addFile($diskPath, $filename);
            }
        }

        // Small, in-memory strings; fine to keep addFromString here.
        foreach ($extraFiles as $filename => $contents) {
            $filename = "{$root}{$filename}";

            $completeZip->addFromString($filename, $contents);
            $updateZip->addFromString($filename, $contents);
        }

        $noteFile = "{$root}models/Note{$release->short}CA.txt";
        $notePath = Storage::path($notes);

        $completeZip->addFile($notePath, $noteFile);
        $updateZip->addFile($notePath, $noteFile);

        Part::with('body')
            ->select(['id', 'created_at', 'type', 'filename', 'header', 'part_release_id'])
            ->official()
            ->orderBy('filename')
            ->chunk(
                500,
                function (Collection $parts) use ($completeZip, $updateZip, $release, $root, $scratch) {
                    foreach ($parts as $part) {
                        // Stage once, add to both zips.
                        $staged = $this->stagePart($scratch, $part);

                        $this->addFileToZip($completeZip, $staged, $root.$part->filename, $part);

                        if ($part->part_release_id === $release->id) {
                            $this->addFileToZip($updateZip, $staged, $root.$part->filename, $part);
                        }
                    }
                }
            );

        // Staged files are read here, so the scratch dir must outlive close().
        $completeZip->close();
        $updateZip->close();
        $scratch->delete();
    }

    protected function newScratch(): TemporaryDirectory
    {
        // Keep off /tmp: in Docker it is often RAM-backed or small.
        return TemporaryDirectory::make()->deleteWhenDestroyed();
    }

    protected function stagePart(TemporaryDirectory $scratch, Part $part): string
    {
        $path = $scratch->path("{$part->id}.dat");
        file_put_contents($path, $part->get());

        return $path;
    }

    protected function addFileToZip(ZipArchive $zip, string $stagedPath, string $filename, Part $part): void
    {
        $flags = $part->isTexmap() ? ZipArchive::FL_ENC_RAW : 0;

        $zip->addFile($stagedPath, $filename, flags: $flags);
        $zip->setMtimeName($filename, $part->lastChange()->getTimestamp());
    }

    protected function addPartToZip(ZipArchive $zip, Part $part, TemporaryDirectory $scratch, string $prefix = ''): void
    {
        $this->addFileToZip($zip, $this->stagePart($scratch, $part), $prefix.$part->filename, $part);
    }
}
