<?php

namespace App\LDraw;

use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Collection;

class ZipFiles
{
    public static function unofficialZip(Part $part, ?string $oldfilename = null): void
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new \ZipArchive();
        if (Storage::disk('library')->exists('unofficial/ldrawunf.zip')) {
            $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'));
            if (!is_null($oldfilename)) {
                $zip->deleteName($oldfilename);
            }
            $path = $tempDir->path($part->filename);
            file_put_contents($path, $part->get());
            $time = (int) $part->lastChange()->format('U');
            touch($path, $time);
            if ($part->isTexmap()) {
                $zip->addFile($path, $part->filename, 0, 0, \ZipArchive::FL_ENC_RAW);
            } else {
                $zip->addFile($path, $part->filename);
            }
            $zip->setMtimeName($part->filename, $time);
            $zip->setCompressionName($part->filename, \ZipArchive::CM_DEFAULT);
            $zip->close();
        } else {
            $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFile(Storage::disk('library')->path('official/CAreadme.txt'), 'CAreadme.txt');
            $zip->addFile(Storage::disk('library')->path('official/CAlicense.txt'), 'CAlicense.txt');
            $zip->addFile(Storage::disk('library')->path('official/CAlicense4.txt'), 'CAlicense4.txt');
            $zip->close();
            Part::unofficial()->chunk(500, function (Collection $parts) use ($zip, $tempDir) {
                $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'));
                $parts->each(function (Part $part) use ($zip, $tempDir) {
                    $path = $tempDir->path($part->filename);
                    file_put_contents($path, $part->get());
                    $time = (int) $part->lastChange()->format('U');
                    touch($path, $time);
                    if ($part->isTexmap()) {
                        $zip->addFile($path, $part->filename, 0, 0, \ZipArchive::FL_ENC_RAW);
                    } else {
                        $zip->addFile($path, $part->filename);
                    }
                    $zip->setMtimeName($part->filename, $time);
                    $zip->setCompressionName($part->filename, \ZipArchive::CM_DEFAULT);
                });
                $zip->close();
            });
        }
    }
}
