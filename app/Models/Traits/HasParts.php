<?php

namespace App\Models\Traits;

use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasParts
{
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
