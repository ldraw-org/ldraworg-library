<?php
namespace App\LDraw;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

use App\Models\Part;
use App\Models\PartRelease;

class LibraryOperations {
  public static function unofficialZip(Part $part) {

  }

  public static function releaseZip(PartRelease $release, array $newSupportFiles) {
    $uzip = new \ZipArchive;
    $uzip->open(storage_path('app/library/updates/staging/lcad'. $release->short . '.zip'), \ZipArchive::CREATE);

    $zip = new \ZipArchive;
    $zip->open(storage_path('app/library/updates/staging/complete.zip'), \ZipArchive::CREATE);

    foreach (Storage::disk('library')->allFiles('official') as $filename) {
      $zipfilename = str_replace('official/', '', $filename);
      $content = Storage::disk('library')->get($filename);
      $zip->addFromString('ldraw/' . $zipfilename, $content);
      if (in_array($zipfilename, $newSupportFiles))
        $uzip->addFromString('ldraw/' . $zipfilename, $content);
    }
    $zip->close();

    // This has to be chunked because php doesn't write the file to disk immediately
    // Trying to hold the entire library in memory will cause an OOM error
    Part::official()->chunk(500, function (Collection $parts) use ($zip, $uzip, $release) {
      $zip->open(storage_path('app/library/updates/staging/complete.zip'));
      foreach($parts as $part) {
        $content = $part->get();
        $zip->addFromString('ldraw/' . $part->filename, $content);
        if ($part->part_release_id == $release->id) 
          $uzip->addFromString('ldraw/' . $part->filename, $content);
      }
      $zip->close();
    });
  }
}