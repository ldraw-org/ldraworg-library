<?php

namespace App\Models\Traits;

use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPart
{
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id', 'id');
    }
}
