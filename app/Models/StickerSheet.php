<?php

namespace App\Models;

use App\Enums\PartCategory;
use App\Models\Part\Part;
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
    protected $guarded = [];

    protected function casts(): array
    {
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
            ->reject(function (Part $p) {
                $hasShortcut = $p->parents->where('category', PartCategory::StickerShortcut)->isNotEmpty();
                $name = basename($p->filename, '.dat');
                $hasFormed = $this->parts()->where('filename', 'LIKE', "parts/{$name}c__.dat")->where('filename', '<>', $p->filename)->exists();
                return $hasShortcut || $hasFormed;
            });
    }
}
