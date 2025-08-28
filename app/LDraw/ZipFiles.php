<?php

namespace App\LDraw;

use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Collection;
use ZipArchive;

class ZipFiles
{
    public static function partZip(Part $part): ?string
    {
        if (!$part->type->inPartsFolder()) {
            return null;
        }
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new ZipArchive();
        $name = basename($part->filename, '.dat') . '.zip';
        $zip->open($dir->path($name), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($part->isUnofficial()) {
            $zipparts = $part->descendantsAndSelf()->doesntHave('unofficial_part')->get()->unique();
        } else {
            $zipparts = $part->descendantsAndSelf->official()->unique();
        }
        $zipparts->each(function (Part $part) use ($zip) {
            $zip->addFromString($part->filename, $part->get());
        });
        $zip->close();
        $file = file_get_contents($dir->path($name));
        return $file ?: '';
    }

    public static function unofficialZip(?Part $part = null, ?string $oldfilename = null): void
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new ZipArchive();
        if (Storage::disk('library')->exists('unofficial/ldrawunf.zip')) {
            $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'));
            if (!is_null($oldfilename)) {
                $zip->deleteName($oldfilename);
            }
            self::addPartToZip($zip, $part, $tempDir);
            $zip->close();
        } else {
            $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile(Storage::disk('library')->path('official/CAreadme.txt'), 'CAreadme.txt');
            $zip->addFile(Storage::disk('library')->path('official/CAlicense.txt'), 'CAlicense.txt');
            $zip->addFile(Storage::disk('library')->path('official/CAlicense4.txt'), 'CAlicense4.txt');
            $zip->close();
            Part::unofficial()->chunk(500, function (Collection $parts) use ($zip, $tempDir) {
                $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'));
                $parts->each(fn (Part $part) => self::addPartToZip($zip, $part, $tempDir));
                $zip->close();
            });
        }
    }

    public static function releaseZips(PartRelease $release, array $extraFiles, string $notes, bool $includeLDConfig, TemporaryDirectory $tempDir): void
    {
        $updateZipName = $tempDir->path("lcad{$release->short}.zip");
        $completeZipName = $tempDir->path("complete.zip");

        $updateZip = new ZipArchive();
        $updateZip->open($updateZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $completeZip = new ZipArchive();
        $completeZip->open($completeZipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (Storage::disk('library')->allFiles('official') as $file) {
            $filename = str_replace('official/', 'ldraw/', $file);
            $completeZip->addFromString($filename, Storage::disk('library')->get($file));
            if (($file == 'official/LDConfig.ldr' || $file == 'official/LDCfgalt.ldr') && $includeLDConfig) {
                $updateZip->addFromString($filename, Storage::disk('library')->get($file));
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

        Part::official()->chunk(500, function (Collection $parts) use ($updateZip, $completeZip, $updateZipName, $completeZipName, $tempDir, $release) {
            $updateZip->open($updateZipName);
            $completeZip->open($completeZipName);
            $parts->each(function (Part $part) use ($updateZip, $completeZip, $tempDir, $release) {
                self::addPartToZip($completeZip, $part, $tempDir, 'ldraw/');
                if ($part->part_release_id == $release->id || $part->has_minor_edit) {
                    self::addPartToZip($updateZip, $part, $tempDir, 'ldraw/');
                }
            });
            $updateZip->close();
            $completeZip->close();
        });
    }

    protected static function addPartToZip(ZipArchive $zip, Part $part, ?TemporaryDirectory $tempDir = null, $prefix = ''): void
    {
        if (is_null($tempDir)) {
            $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        }
        $path = $tempDir->path($part->filename);
        file_put_contents($path, $part->get());
        $time = (int) $part->lastChange()->format('U');
        touch($path, $time);
        if ($part->isTexmap()) {
            $zip->addFile($path, $prefix . $part->filename, 0, 0, ZipArchive::FL_ENC_RAW);
        } else {
            $zip->addFile($path, $prefix . $part->filename);
        }
        $zip->setMtimeName($part->filename, $time);
        $zip->setCompressionName($part->filename, ZipArchive::CM_DEFAULT);
    }
}
