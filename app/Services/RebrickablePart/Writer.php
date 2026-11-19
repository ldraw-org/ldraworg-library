<?php

namespace App\Services\RebrickablePart;

use App\Models\Part\Part;
use App\Models\RebrickablePart;
use Illuminate\Support\Arr;

class Writer
{
    public function updateOrCreateFromArray(array $partData): RebrickablePart
    {
        $values = [
            'number' => Arr::get($partData, 'part_num'),
            'name' => Arr::get($partData, 'name', 'Unknown'),
            'url' => Arr::get($partData, 'part_url'),
            'image_url' => Arr::get($partData, 'part_img_url'),
            'bricklink' => Arr::get($partData, 'external_ids.BrickLink', []),
            'brickset' => Arr::get($partData, 'external_ids.Brickset', []),
            'brickowl' => Arr::get($partData, 'external_ids.BrickOwl', []),
            'lego' => Arr::get($partData, 'external_ids.LEGO', []),
            'rb_part_category_id' => Arr::get($partData, 'part_cat_id'),
            'refreshed_at' => now(),
        ];

        // Not every source provides these, so a missing value must not erase a stored one
        $optionalValues = Arr::whereNotNull(Arr::only($partData, ['element', 'print_of']));

        return RebrickablePart::updateOrCreate(
            ['number' => $values['number']],
            [...$values, ...$optionalValues]
        );
    }

    /**
     * Finds the imported Rebrickable part the way the API's ldraw_id lookup does: a part mapped to the
     * LDraw number (the same number first, then the lowest), else a part with the same number.
     */
    public function findForPart(Part $part): ?RebrickablePart
    {
        $partNum = basename($part->filename, '.dat');

        $mapped = RebrickablePart::where('is_local', false)
            ->whereJsonContains('ldraw', $partNum)
            ->get();

        return $mapped->firstWhere('number', $partNum)
            ?? $mapped->sortBy('number', SORT_STRING)->first()
            ?? RebrickablePart::where('is_local', false)->firstWhere('number', $partNum);
    }

    /**
     * Links the part to its Rebrickable part, leaving the existing link alone when nothing matches.
     */
    public function findOrCreateFromPart(Part $part): ?RebrickablePart
    {
        $rb = $this->findForPart($part);

        if (is_null($rb)) {
            return null;
        }

        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }
}
