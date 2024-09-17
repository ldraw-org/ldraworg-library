<?php

namespace App\LDraw;

use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use App\Models\Part;

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
            $zip->addFile($path, $part->filename);
            $zip->setMtimeName($part->filename, $time);
            $zip->setCompressionName($part->filename, \ZipArchive::CM_DEFAULT);
            $zip->close();
        } else {
            $zip->open(Storage::disk('library')->path('unofficial/ldrawunf.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            Part::unofficial()->each(function (Part $part) use ($zip, $tempDir) {
                $path = $tempDir->path($part->filename);
                file_put_contents($path, $part->get());
                $time = (int) $part->lastChange()->format('U');
                touch($path, $time);
                $zip->addFile($path, $part->filename);
                $zip->setMtimeName($part->filename, $time);
                $zip->setCompressionName($part->filename, \ZipArchive::CM_DEFAULT);
            });
            $zip->close();
        }
    }
}
