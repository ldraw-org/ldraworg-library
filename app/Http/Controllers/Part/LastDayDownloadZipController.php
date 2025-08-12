<?php

namespace App\Http\Controllers\Part;

use ZipArchive;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Builder;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LastDayDownloadZipController extends Controller
{
    public function __invoke()
    {
        $dir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $zip = new ZipArchive();
        $name = 'ldrawunf-last-day.zip';
        $zip->open($dir->path($name), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        Part::whereHas(
            'events',
            fn (Builder $q) =>
                $q->where('created_at', '>=', now()->subDay())
                    ->whereIn(
                        'event_type',
                        [EventType::Submit, EventType::Rename, EventType::HeaderEdit]
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
