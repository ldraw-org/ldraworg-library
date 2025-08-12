<?php

namespace App\Models\Traits;

use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPartRelease
{
    public function release(): BelongsTo
    {
        return $this->belongsTo(PartRelease::class, 'part_release_id', 'id');
    }

    #[Scope]
    protected function official(Builder $query): void
    {
        $query->whereNotNull('part_release_id');
    }

    #[Scope]
    protected function unofficial(Builder $query): void
    {
        $query->whereNull('part_release_id');
    }
}
