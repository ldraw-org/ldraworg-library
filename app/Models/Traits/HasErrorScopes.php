<?php

namespace App\Models\Traits;

use App\Enums\CheckType;
use App\Enums\PartError;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasErrorScopes
{
    #[Scope]
    protected function hasErrors(Builder $query): void
    {
        $this->hasCheckType($query, CheckType::Error);
    }

    #[Scope]
    protected function hasWarnings(Builder $query): void
    {
        $this->hasCheckType($query, CheckType::Warning);
    }

    #[Scope]
    protected function hasTrackerHolds(Builder $query): void
    {
        $this->hasCheckType($query, CheckType::TrackerHold);
    }

    private function hasCheckType(Builder $query, CheckType $type): void
    {
        $query->whereJsonContains('check_messages', ['checkType' => $type->value]);
    }

    #[Scope]
    protected function hasMessage(Builder $query, PartError|string $error): void
    {
        $this->applyMessageFilter($query, $error);
    }

    #[Scope]
    protected function orHasMessage(Builder $query, PartError|string $error): void
    {
        $this->applyMessageFilter($query, $error, true);
    }

    #[Scope]
    protected function doesntHaveMessage(Builder $query, PartError|string $error): void
    {
        $this->applyMessageFilter($query, $error, false, true);
    }

    #[Scope]
    protected function orDoesntHaveMessage(Builder $query, PartError|string $error): void
    {
        $this->applyMessageFilter($query, $error, true, true);
    }

    private function applyMessageFilter(
        Builder $query,
        PartError|string $error,
        bool $useOr = false,
        bool $negate = false
    ): void {
        $value = $error instanceof PartError ? $error->value : $error;

        // Use the appropriate Laravel JSON method directly
        $method = match (true) {
            $useOr && $negate => 'orWhereJsonDoesntContain',
            $useOr => 'orWhereJsonContains',
            $negate => 'whereJsonDoesntContain',
            default => 'whereJsonContains',
        };

        $query->{$method}('check_messages', ['error' => $value]);
    }
}
