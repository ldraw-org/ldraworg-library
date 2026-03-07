<?php

namespace App\Services\LDraw;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Collection;
use ZipArchive;

class ZipFiles
{
    public function partZip(Part $part): ?string
    {
        if (!$part->type->inPartsFolder()) {
            return null;
        }
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new ZipArchive();
        $name = basename($part->filename, '.dat') . '.zip';
        $zip->open($dir->path($name), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $query = $part->isUnofficial()
            ? $part->descendantsAndSelf()->doesntHave('unofficial_part')
            : $part->descendantsAndSelf()->official();

        $seen = [];

        foreach ($query->cursor() as $zipPart) {
            $seen[$zipPart->id] = true;
            self::addPartToZip($zip, $zipPart);
        }
        $zip->close();
        $file = file_get_contents($dir->path($name));
        return $file ?: '';
    }

    public function unofficialZip(?Part $part = null, ?string $oldfilename = null): void
    {
        $zip = new ZipArchive();
        if (Storage::exists('library/unofficial/ldrawunf.zip')) {
            $zip->open(Storage::path('library/unofficial/ldrawunf.zip'));
            if (!is_null($oldfilename)) {
                $zip->deleteName($oldfilename);
            }
            self::addPartToZip($zip, $part);
            $zip->close();
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
                    function (Collection $parts) use ($zip) {
                        $parts->each(fn (Part $part) => self::addPartToZip($zip, $part));
                    });
        }
    }

    public function releaseZips(PartRelease $release, array $extraFiles, string $notes, bool $includeLDConfig, string $path): void
    {

        $root = 'ldraw/';

        $updateZipName = "{$path}/lcad{$release->short}.zip";
        $completeZipName = "{$path}/complete.zip";

        $updateZip = new ZipArchive();
        $updateZip->open($updateZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $completeZip = new ZipArchive();
        $completeZip->open($completeZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

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
            ->select(['id', 'created_at', 'type', 'filename', 'header'])
            ->official()
            ->orderBy('filename')
            ->chunk(
                500,
                function (Collection $parts) use ($completeZip, $updateZip, $release, $root) {
                    foreach ($parts as $part) {
                        $filename = $root . $part->filename;
                        $timestamp = $part->lastChange()->getTimestamp();
                        $flags = $part->isTexmap() ? ZipArchive::FL_ENC_RAW : 0;

                        $contents = $part->get();

                        $this->addStringToZip($completeZip, $filename, $contents, $timestamp, $flags);

                        if ($part->part_release_id === $release->id || $part->has_minor_edit) {
                            $this->addStringToZip($updateZip, $filename, $contents, $timestamp, $flags);
                        }
                    }

                });

        $completeZip->close();
        $updateZip->close();
    }

    protected function addStringToZip(
        ZipArchive $zip,
        string $filename,
        string $contents,
        int $timestamp,
        int $flags = 0
    ): void {
        $zip->addFromString($filename, $contents, flags: $flags);
        $zip->setMtimeName($filename, $timestamp);
    }

    protected function addPartToZip(ZipArchive $zip, Part $part, string $prefix = ''): void
    {
        $filename = $prefix . $part->filename;
        $timestamp = $part->lastChange()->getTimestamp();

        $flags = $part->isTexmap() ? ZipArchive::FL_ENC_RAW : 0;
        $zip->addFromString($filename, $part->get(), flags: $flags);
        $zip->setMtimeName($filename, $timestamp);
    }

}
