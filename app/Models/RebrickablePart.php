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
}
