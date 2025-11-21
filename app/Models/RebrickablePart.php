<?php

namespace App\Models;

use App\Services\LDraw\Rebrickable;
use App\Models\Omr\Set;
use App\Models\Part\Part;
use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class RebrickablePart extends Model
{
    use HasParts;

    protected $guarded = [];

    /**
     * @return array{
     *     'bricklink': 'array',
     *     'brickowl': 'array',
     *     'brickset': 'array',
     *     'lego': 'array',
     *     'is_local': 'boolean',
     *     'refreshed_at': 'datetime'
     * }
     */
    protected function casts(): array
    {
        return [
            'bricklink' => 'array',
            'brickowl' => 'array',
            'brickset' => 'array',
            'lego' => 'array',
            'is_local' => 'boolean',
            'refreshed_at' => 'datetime',
        ];
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(Set::class, 'rebrickable_parts_sets', 'rebrickable_part_id', 'set_id')
                    ->withPivot('ldraw_colour_id', 'quantity');
    }

    #[Scope]
    protected function sticker_sheets(Builder $query): void
    {
        $query->where('rb_part_category_id', 58);
    }

    #[Scope]
    protected function exclude_stickers(Builder $query): void
    {
        $query->where('rb_part_category_id', '!=', 58);
    }

    public static function updateOrCreateFromArray(array $partData): self
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

        return static::updateOrCreate(
            ['number' => $values['number']],
            $values
        );
    }

    public static function findOrCreateFromPart(Part $part, Rebrickable $rbService): ?self
    {
        $partNum = basename($part->filename, '.dat');

        $rbParts = $rbService->getParts(['ldraw_id' => $partNum]);
        if ($rbParts->isEmpty()) {
            return null;
        }

        $rbData = $rbParts->firstWhere('part_num', $partNum) ?? $rbParts->first();
        if (!$rbData) {
            return null;
        }

        $rb = static::updateOrCreateFromArray($rbData);

        $part->rebrickable_part()->associate($rb);
        $part->save();

        return $rb;
    }
}
