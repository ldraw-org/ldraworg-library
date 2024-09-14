<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LastDayDownloadZipController extends Controller
{
    public function __invoke()
    {
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new \ZipArchive();
        $name = 'ldrawunf-last-day.zip';
        $zip->open($dir->path($name), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        Part::whereHas(
            'events',
            fn (Builder $q) =>
                $q->where('created_at', '>=', now()->subDay())->whereHas(
                    'part_event_type',
                    fn (Builder $qu) =>
                    $qu->whereIn('slug', ['submit', 'rename', 'edit'])
                )
        )
            ->each(
                fn (Part $part) =>
                $zip->addFromString($part->filename, $part->get())
            );
        $zip->close();
        $contents = file_get_contents($dir->path($name));
        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $name,
            [
                'Content-Type' => 'application/zip',
            ]
        );
    }
}
