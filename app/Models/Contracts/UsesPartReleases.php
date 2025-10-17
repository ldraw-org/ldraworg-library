<?php

namespace App\Collections\Contracts;

use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface UsesPartReleases
{
    public function release(): BelongsTo;

    #[Scope]
    protected function official(Builder $query): void;

    #[Scope]
    protected function unofficial(Builder $query): void;
}
