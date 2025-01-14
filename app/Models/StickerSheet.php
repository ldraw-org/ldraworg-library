<?php

namespace App\Models;

use App\Models\Part\Part;
use App\Models\Rebrickable\RebrickablePart;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStickerSheet
 */
class StickerSheet extends Model
{
    protected $fillable = [
        'number',
        'rebrickable_part_id',
        'rebrickable',
        'ldraw_colour_id',
        'part_colors'
    ];

    protected function casts(): array {
        return [
            'rebrickable' => AsArrayObject::class,
            'part_colors' => AsArrayObject::class
        ];
    }

    public function rebrickable_part(): BelongsTo
    {
        return $this->BelongsTo(RebrickablePart::class, 'rebrickable_part_id', 'id');
    }

    public function color(): BelongsTo
    {
        return $this->BelongsTo(LdrawColour::class, 'ldraw_colour_id', 'id');
    }

    public function parts(): HasMany
    {
        return $this->HasMany(Part::class, 'sticker_sheet_id', 'id');
    }

    public function complete_set(): Collection
    {
        return $this->parts->whereNull('unofficial_part')
            ->reject(function (Part $p){
                $hasShortcut = $p->parents->where('category.category', 'Sticker Shortcut')->count() > 0;
                $name = basename($p->filename, '.dat');
                $hasFormed = $this->parts()->where('filename', 'LIKE', "parts/{$name}c__.dat")->where('filename', '<>', $p->filename)->count() > 0;
                return $hasShortcut || $hasFormed;
            });
    }
}
