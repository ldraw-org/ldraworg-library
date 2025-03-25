<?php

namespace App\Jobs;

use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class PurgeOrphanImages implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $images = Storage::disk('images')->allFiles('library/unofficial');
        $files = collect($images)
            ->map(function (string $file): string {
                $file = str_replace('_thumb.png', '.png', $file);
                if (strpos($file, 'textures/') !== false) {
                    return str_replace('library/unofficial/', '', $file);
                } else {
                    return str_replace(['library/unofficial/', '.png'], ['', '.dat'], $file);
                }
            })
            ->unique()
            ->all();
        $in_use_files = Part::unofficial()
            ->whereIn('filename', $files)
            ->pluck('filename')
            ->map(
                fn (string $filename): string =>
                    strpos($filename, 'textures/') !== false ? "library/unofficial/{$filename}" : str_replace('.dat', '.png', "library/unofficial/{$filename}")
            );
        foreach ($images as $image) {
            if (!$in_use_files->contains($image) && !$in_use_files->contains(str_replace('_thumb.png', '.png', $image))) {
                Storage::disk('images')->delete($image);
            }
        }
    }
}
