<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasLicense
{
    public function license(): BelongsTo 
    {
        return $this->belongsTo(\App\Models\PartLicense::class, 'part_license_id', 'id');
    }

    public function scopeLicenseName(Builder $query, string $name): void
    {
        $query->whereRelation('license', 'name', $name);
    }

    public function scopeNotLicenseName(Builder $query, string $name): void
    {
        $query->whereRelation('license', 'name', '<>', $name);
    }
}