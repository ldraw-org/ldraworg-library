<?php

namespace App\Services\LDraw\Managers;

use App\Models\RebrickablePart;
use App\Models\Omr\Set;
use App\Services\Cache\CacheService;
use App\Services\External\Rebrickable;
use Illuminate\Support\Facades\Cache;

class SetManager
{

    public function __construct(
        protected Rebrickable $rebrickable,
        protected RebrickablePartManager $rebrickablePartManager,
        protected LDConfigManager $configManager,
    )
    {}

    public function updateOrCreateSetFromArray(array $setData): Set
    {
        $set = Set::updateOrCreate(
            ['number' => $setData['set_num']],
            [
                'name' => $setData['name'],
                'year' => $setData['year'],
                'theme_id' => $setData['theme_id'],
                'rb_url' => $setData['set_url'],
            ]
        );
        if ($set->rebrickable_parts()->doesntExist() ||
            $set->refreshed_at ?? now() < now()->subMonth()) {
            $this->refreshSetParts($set);
            $set->refreshed_at = now();
            $set->save();
        }
        return $set;
    }

    public function refreshSetParts(Set $set): void
    {
        $set->rebrickable_parts()->sync([]);
        $parts = $this->rebrickable->getSetParts($set->number);
        $colors = collect(collect($this->configManager->ldrawColourCodesToRebrickable()));
        $parts->each(function (array $part) use ($set, $colors) {
            $part_vals = $part['part'];
            $part_vals['element'] = $part['element_id'];
            $rbPart = $this->rebrickablePartManager->updateOrCreateFromArray($part_vals);
            $color = $colors->search(fn($value, $key) => $value === $part['color']['id']) ?? $part['color']['id'];
            $set->rebrickable_parts()->attach($rbPart->id, ['quantity' => $part['quantity'], 'ldraw_colour_id' => $color]);
        });
    }
}
