<?php

namespace App\Services\Support;

use App\Models\Part\Part;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use RuntimeException;

class MakeLibraryCsv
{
    private const HEADERS = ['part_number', 'part_description', 'part_url', 'image_url', 'image_last_modified', 'category'];

    public function handle(): void
    {
        $stream = tmpfile();

        $csv = Writer::from($stream);
        $csv->setEscape('');
        $csv->insertOne(self::HEADERS);
        $csv->insertAll($this->rows());

        rewind($stream);
        Storage::writeStream('library/library.csv', $stream);
        fclose($stream);
    }

    private function rows(): \Generator
    {
        $parts = Part::select('id', 'filename', 'description', 'part_release_id', 'created_at', 'category')
            ->with(['media' => fn ($query) => $query->where('collection_name', 'image')])
            ->doesntHave('official_part')
            ->partsFolderOnly()
            ->activeParts()
            ->whereNull('type_qualifier')
            ->whereNotLike('description', '|%')
            ->whereNotLike('description', '~%')
            ->lazyById(500);

        foreach ($parts as $part) {
            if ($part->category === null) {
                throw new RuntimeException("Part {$part->filename} has no category.");
            }

            $media = $part->getFirstMedia('image');

            yield [
                basename($part->filename),
                $part->description,
                route('part.download', [
                    'library' => is_null($part->part_release_id) ? 'unofficial' : 'official',
                    'filename' => $part->filename,
                ]),
                $part->getFirstMediaUrl('image'),
                ($media?->created_at ?? $part->created_at)->format('Y-m-d'),
                $part->category->value,
            ];
        }
    }
}
