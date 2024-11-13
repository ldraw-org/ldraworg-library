<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPartRelease
{
    public function release(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Part\PartRelease::class, 'part_release_id', 'id');
    }

    public function scopeOfficial(Builder $query): void
    {
        $query->whereNotNull('part_release_id');
    }

    public function scopeUnofficial(Builder $query): void
    {
        $query->whereNull('part_release_id');
    }
}
