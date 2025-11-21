<?php

namespace App\Services\LDraw\Managers;

use App\Models\RebrickablePart;
use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Services\LDraw\Rebrickable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class RebrickablePartManager
{

    public function __construct(
        protected Rebrickable $rebrickable,
    )
    {}

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
            'element' => Arr::get($partData, 'element'),
            'refreshed_at' => now(),
        ];

        return RebrickablePart::updateOrCreate(
            ['number' => $values['number']],
            $values
        );
    }

    public function findOrCreateFromPart(Part $part): ?RebrickablePart
    {
        $partNum = basename($part->filename, '.dat');

        $rbParts = $this->rebrickable->getParts(['ldraw_id' => $partNum]);
        if ($rbParts->isEmpty()) {
            return null;
        }

        $rbData = $rbParts->firstWhere('part_num', $partNum) ?? $rbParts->first();
        if (!$rbData) {
            return null;
        }

        $rb = $this->updateOrCreateFromArray($rbData);

        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }
}
