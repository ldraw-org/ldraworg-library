<?php

namespace App\Models\Traits;

use App\Enums\PartError;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasErrorScopes
{
    #[Scope]
    protected function hasErrors(Builder $query): void
    {
        $query->whereJsonLength('errors', '>', 0);
    }

    #[Scope]
    protected function doesntHaveErrors(Builder $query): void
    {
        $query->whereJsonLength('errors', 0);
    }

    #[Scope]
    protected function hasWarnings(Builder $query): void
    {
        $query->whereJsonLength('warnings', '>', 0);
    }

    #[Scope]
    protected function doesntHaveWarnings(Builder $query): void
    {
        $query->whereJsonLength('warnings', 0);
    }

    #[Scope]
    protected function hasTrackerHolds(Builder $query): void
    {
        $query->whereJsonLength('tracker_holds', '>', 0);
    }

    #[Scope]
    protected function doesntHaveTrackerHolds(Builder $query): void
    {
        $query->whereJsonLength('tracker_holds', 0);
    }

    #[Scope]
    protected function hasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonContains('errors', ['error' => $error]);
    }

    #[Scope]
    protected function orHasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonContains('errors', ['error' => $error]);
    }

    #[Scope]
    protected function doesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonDoesntContain('errors', ['error' => $error]);
    }

    #[Scope]
    protected function orDoesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonDoesntContain('errors', ['error' => $error]);
    }

    #[Scope]
    protected function hasWarning(Builder $query, string|PartError $warning): void
    {
        if ($warning instanceof PartError) {
            $warning = $warning->value;
        }
        $query->whereJsonContains('warnings', ['error' => $warning]);
    }

    #[Scope]
    protected function orHasWarning(Builder $query, string|PartError $warning): void
    {
        if ($warning instanceof PartError) {
            $warning = $warning->value;
        }
        $query->orWhereJsonContains('warnings', ['error' => $warning]);
    }

    #[Scope]
    protected function doesntHaveWarning(Builder $query, string|PartError $warning): void
    {
        if ($warning instanceof PartError) {
            $warning = $warning->value;
        }
        $query->whereJsonDoesntContain('warnings', ['error' => $warning]);
    }

    #[Scope]
    protected function orDoesntHaveWarning(Builder $query, string|PartError $warning): void
    {
        if ($warning instanceof PartError) {
            $warning = $warning->value;
        }
        $query->orWhereJsonDoesntContain('warnings', ['error' => $warning]);
    }

}
