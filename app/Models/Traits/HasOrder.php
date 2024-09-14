<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasOrder
{
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order', 'asc');
    }

    public static function nextOrder(): int
    {
        $maxOrder = self::orderBy('order', 'desc')->first()->order ?? 0;
        return $maxOrder + 1;
    }
}
