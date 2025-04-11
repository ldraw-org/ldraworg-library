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
        $query->whereJsonLength('part_check->errors', '>', 0);
    }

    #[Scope]
    protected function doesntHaveErrors(Builder $query): void
    {
        $query->whereJsonLength('part_check->errors', '=', 0);
    }

    #[Scope]
    protected function hasWarnings(Builder $query): void
    {
        $query->whereJsonLength('part_check->warnings', '>', 0);
    }

    #[Scope]
    protected function doesntHaveWarnings(Builder $query): void
    {
        $query->whereJsonLength('part_check->warnings', '=', 0);
    }

    #[Scope]
    protected function hasTracker(Builder $query): void
    {
        $query->whereJsonLength('part_check->tracker_holds', '>', 0);
    }

    #[Scope]
    protected function doesntHaveTracker(Builder $query): void
    {
        $query->whereJsonLength('part_check->tracker_holds', '=', 0);
    }

    #[Scope]
    protected function hasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonContainsKey("part_check->errors->{$error}");
    }

    #[Scope]
    protected function orHasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonContainsKey("part_check->errors->{$error}");
    }

    #[Scope]
    protected function doesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonDoesntContainKey("part_check->errors->{$error}");
    }

    #[Scope]
    protected function orDoesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonDoesntContainKey("part_check->errors->{$error}");
    }
}