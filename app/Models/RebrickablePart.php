<?php

namespace App\Models;

use App\Models\Omr\Set;
use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read object{ldraw_colour_id: ?int, quantity: ?int, is_spare: bool} $pivot
 */
#[Unguarded]
class RebrickablePart extends Model
{
    use HasParts;

    /**
     * @return array{
     *     'bricklink': 'array',
     *     'brickowl': 'array',
     *     'brickset': 'array',
     *     'lego': 'array',
     *     'ldraw': 'array',
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
            'ldraw' => 'array',
            'is_local' => 'boolean',
            'refreshed_at' => 'datetime',
        ];
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(Set::class, 'rebrickable_parts_sets', 'rebrickable_part_id', 'set_id')
                    ->withPivot('ldraw_colour_id', 'quantity', 'is_spare');
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
}
