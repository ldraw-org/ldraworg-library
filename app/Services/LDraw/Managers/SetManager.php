<?php

namespace App\Services\LDraw\Managers;

use App\Models\RebrickablePart;
use App\Models\Omr\Set;
use App\Services\LDraw\Rebrickable;
use Illuminate\Support\Facades\Cache;

class SetManager
{

    public function __construct(
        protected Rebrickable $rebrickable,
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
        $colors = collect(Cache::get('ldraw_colour_codes_to_rebrickable', [])); 
        $parts->each(function (array $part) use ($set, $colors) {
            $part_vals = $part['part']; 
            $part_vals['element'] = $part['element_id']; 
            $rbPart = RebrickablePart::updateOrCreateFromArray($part_vals); 
            $color = $colors->search(fn($value, $key) => $value === $part['color']['id']) ?? $part['color']['id']; 
            $set->rebrickable_parts()->attach($rbPart->id, ['quantity' => $part['quantity'], 'ldraw_colour_id' => $color]); 
        });
    }    
}
