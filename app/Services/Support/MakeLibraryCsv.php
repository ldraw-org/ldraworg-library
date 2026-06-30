<?php

namespace App\Services\Support;

use App\Models\Part\Part;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class MakeLibraryCsv
{
    public function handle(): void
    {
        $headers = ['part_number', 'part_description', 'part_url', 'image_url', 'image_last_modified', 'category'];

        $rows = Part::select('id', 'filename', 'description', 'part_release_id', 'created_at', 'category')
            ->with('media')
            ->doesntHave('official_part')
            ->partsFolderOnly()
            ->activeParts()
            ->whereNull('type_qualifier')
            ->whereNotLike('description', '|%')
            ->whereNotLike('description', '~%')
            ->get()
            ->map(function (Part $part) {
                $media = $part->getFirstMedia('image');
                $imageDate = ($media?->created_at ?? $part->created_at)->format('Y-m-d');
                if ($part->category === null) dd($part->filename);
                return [
                    basename($part->filename),
                    $part->description,
                    route('part.download', [
                        'library'   => is_null($part->part_release_id) ? 'unofficial' : 'official',
                        'filename'  => $part->filename,
                    ]),
                    $part->getFirstMediaUrl('image'),
                    $imageDate,
                    $part->category->value,
                ];
            });

        $csv = Writer::fromString();
        $csv->setEscape('');
        $csv->insertOne($headers);
        $csv->insertAll($rows);
        Storage::put('library/library.csv', $csv->toString());
    }
}
