<?php

namespace App\Models;

use App\Models\Rebrickable\RebrickablePart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StickerSheet extends Model
{
    protected $fillable = [
        'number',
        'rebrickable_part_id'
    ];

    public function rebrickable_part(): BelongsTo
    {
        return $this->BelongsTo(RebrickablePart::class, 'rebrickable_part_id', 'id');
    }

    public function parts(): HasMany
    {
        return $this->HasMany(Part::class, 'sticker_sheet_id', 'id');
    }

}
