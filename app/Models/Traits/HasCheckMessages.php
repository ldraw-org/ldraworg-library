<?php

namespace App\Models\Traits;

use App\Models\CheckMessage;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\CheckType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCheckMessages
{
    public function check_messages(): HasMany
    {
        return $this->hasMany(CheckMessage::class, 'part_id', 'id');
    }

    #[Scope]
    protected function hasErrors(Builder $query): void
    {
        self::hasCheckType($query, CheckType::Error);
    }

    #[Scope]
    protected function hasWarnings(Builder $query): void
    {
        self::hasCheckType($query, CheckType::Warning);
    }

    #[Scope]
    protected function hasTrackerHolds(Builder $query): void
    {
        self::hasCheckType($query, CheckType::TrackerHold);
    }

    #[Scope]
    protected function hasMessage(Builder $query, CheckItem|string $error): void
    {
        self::applyMessageFilter($query, $error, useOr: false, negate: false);
    }

    #[Scope]
    protected function orHasMessage(Builder $query, CheckItem|string $error): void
    {
        self::applyMessageFilter($query, $error, useOr: true, negate: false);
    }

    #[Scope]
    protected function doesntHaveMessage(Builder $query, CheckItem|string $error): void
    {
        self::applyMessageFilter($query, $error, useOr: false, negate: true);
    }

    #[Scope]
    protected function orDoesntHaveMessage(Builder $query, CheckItem|string $error): void
    {
        self::applyMessageFilter($query, $error, useOr: true, negate: true);
    }

    private static function hasCheckType(Builder $query, CheckType $type): Builder
    {
        return $query->whereHas('check_messages', fn (Builder $q) => $q->where('check_type', $type));
    }

    private static function applyMessageFilter(
        Builder $query,
        CheckItem|string $error,
        bool $useOr,
        bool $negate
    ): Builder {
        $method = match (true) {
            $useOr && $negate => 'orWhereDoesntHave',
            $useOr            => 'orWhereHas',
            $negate           => 'whereDoesntHave',
            default           => 'whereHas',
        };

        return $query->{$method}('check_messages', fn (Builder $q) => $q->where('check', $error));
    }

}
