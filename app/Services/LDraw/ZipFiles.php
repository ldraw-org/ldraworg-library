<?php

namespace App\Services\LDraw;

use Illuminate\Support\Facades\Storage;
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
            $zip->open(Storage::path('library/unofficial/ldrawunf.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile(Storage::path('library/official/CAreadme.txt'), 'CAreadme.txt');
            $zip->addFile(Storage::path('library/official/CAlicense.txt'), 'CAlicense.txt');
            $zip->addFile(Storage::path('library/official/CAlicense4.txt'), 'CAlicense4.txt');
            $zip->close();
            Part::unofficial()->chunk(500, function (Collection $parts) use ($zip) {
                $zip->open(Storage::path('library/unofficial/ldrawunf.zip'));
                $parts->each(fn (Part $part) => self::addPartToZip($zip, $part));
                $zip->close();
            });
        }
    }

    public function releaseZips(PartRelease $release, array $extraFiles, string $notes, bool $includeLDConfig, string $path): void
    {
        $updateZipName = "{$path}/lcad{$release->short}.zip");
        $completeZipName = "{$path}/complete.zip");

        $updateZip = new ZipArchive();
        $updateZip->open($updateZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $completeZip = new ZipArchive();
        $completeZip->open($completeZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (Storage::allFiles('library/official') as $file) {
            $filename = str_replace('official/', 'ldraw/', $file);
            $completeZip->addFromString($filename, Storage::get("library/oficial/{$file}"));
            if (($file == 'library/official/LDConfig.ldr' || $file == 'library/official/LDCfgalt.ldr') && $includeLDConfig) {
                $updateZip->addFromString($filename, Storage::get("library/official/{$file}"));
            };
        }

        foreach ($extraFiles as $filename => $contents) {
            $filename = "ldraw/{$filename}";
            $updateZip->addFromString($filename, $contents);
            $completeZip->addFromString($filename, $contents);
        }

        $completeZip->addFromString("ldraw/models/Note{$release->short}CA.txt", $notes);
        $updateZip->addFromString("ldraw/models/Note{$release->short}CA.txt", $notes);

        $updateZip->close();
        $completeZip->close();

        Part::official()->chunk(100, function (Collection $parts) use ($updateZip, $completeZip, $updateZipName, $completeZipName, $release) {
            $updateZip->open($updateZipName);
            $completeZip->open($completeZipName);
            $parts->each(function (Part $part) use ($updateZip, $completeZip, $release) {
                self::addPartToZip($completeZip, $part, 'ldraw/');
                if ($part->part_release_id == $release->id || $part->has_minor_edit) {
                    self::addPartToZip($updateZip, $part, 'ldraw/');
                }
            });
            $updateZip->close();
            $completeZip->close();
        });
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
