<?php

namespace App\Models\Traits;

use App\Enums\PartError;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasErrorScopes
{
    protected array $errorAttributes = ['errors', 'warnings', 'tracker_holds'];

    // -----------------------------------------------------------------
    // Count-based scopes
    // -----------------------------------------------------------------

    #[Scope]
    protected function hasErrors(Builder $query): void
    {
        $this->applyHasItems($query, 'errors');
    }

    #[Scope]
    protected function doesntHaveErrors(Builder $query): void
    {
        $this->applyNoItems($query, 'errors');
    }

    #[Scope]
    protected function hasWarnings(Builder $query): void
    {
        $this->applyHasItems($query, 'warnings');
    }

    #[Scope]
    protected function doesntHaveWarnings(Builder $query): void
    {
        $this->applyNoItems($query, 'warnings');
    }

    #[Scope]
    protected function hasTrackerHolds(Builder $query): void
    {
        $this->applyHasItems($query, 'tracker_holds');
    }

    #[Scope]
    protected function doesntHaveTrackerHolds(Builder $query): void
    {
        $this->applyNoItems($query, 'tracker_holds');
    }

    // -----------------------------------------------------------------
    // Error-based scopes (per column)
    // -----------------------------------------------------------------

    // --- Errors ---
    #[Scope]
    protected function hasError(Builder $query, string|PartError $error): void
    {
        $this->applyJsonCondition($query, 'errors', $error);
    }

    #[Scope]
    protected function orHasError(Builder $query, string|PartError $error): void
    {
        $this->applyJsonCondition($query, 'errors', $error, true);
    }

    #[Scope]
    protected function doesntHaveError(Builder $query, string|PartError $error): void
    {
        $this->applyJsonCondition($query, 'errors', $error, false, true);
    }

    #[Scope]
    protected function orDoesntHaveError(Builder $query, string|PartError $error): void
    {
        $this->applyJsonCondition($query, 'errors', $error, true, true);
    }

    // --- Warnings ---
    #[Scope]
    protected function hasWarning(Builder $query, string|PartError $warning): void
    {
        $this->applyJsonCondition($query, 'warnings', $warning);
    }

    #[Scope]
    protected function orHasWarning(Builder $query, string|PartError $warning): void
    {
        $this->applyJsonCondition($query, 'warnings', $warning, true);
    }

    #[Scope]
    protected function doesntHaveWarning(Builder $query, string|PartError $warning): void
    {
        $this->applyJsonCondition($query, 'warnings', $warning, false, true);
    }

    #[Scope]
    protected function orDoesntHaveWarning(Builder $query, string|PartError $warning): void
    {
        $this->applyJsonCondition($query, 'warnings', $warning, true, true);
    }

    // --- Tracker Holds ---
    #[Scope]
    protected function hasTrackerHold(Builder $query, string|PartError $hold): void
    {
        $this->applyJsonCondition($query, 'tracker_holds', $hold);
    }

    #[Scope]
    protected function orHasTrackerHold(Builder $query, string|PartError $hold): void
    {
        $this->applyJsonCondition($query, 'tracker_holds', $hold, true);
    }

    #[Scope]
    protected function doesntHaveTrackerHold(Builder $query, string|PartError $hold): void
    {
        $this->applyJsonCondition($query, 'tracker_holds', $hold, false, true);
    }

    #[Scope]
    protected function orDoesntHaveTrackerHold(Builder $query, string|PartError $hold): void
    {
        $this->applyJsonCondition($query, 'tracker_holds', $hold, true, true);
    }

    // -----------------------------------------------------------------
    // Combined “any issues” scopes
    // -----------------------------------------------------------------

    #[Scope]
    protected function hasAnyIssues(Builder $query): void
    {
        $query->where(function (Builder $q) {
            foreach ($this->errorAttributes as $attr) {
                $q->orWhereJsonLength($attr, '>', 0);
            }
        });
    }

    #[Scope]
    protected function doesntHaveAnyIssues(Builder $query): void
    {
        $query->where(function (Builder $q) {
            foreach ($this->errorAttributes as $attr) {
                $q->whereJsonLength($attr, 0);
            }
        });
    }

    // -----------------------------------------------------------------
    // NEW: Catch-all issue value scopes
    // -----------------------------------------------------------------

    #[Scope]
    protected function hasIssue(Builder $query, string|PartError $issue): void
    {
        $this->applyJsonConditionAcrossColumns($query, $issue);
    }

    #[Scope]
    protected function orHasIssue(Builder $query, string|PartError $issue): void
    {
        $this->applyJsonConditionAcrossColumns($query, $issue, true);
    }

    #[Scope]
    protected function doesntHaveIssue(Builder $query, string|PartError $issue): void
    {
        $this->applyJsonConditionAcrossColumns($query, $issue, false, true);
    }

    #[Scope]
    protected function orDoesntHaveIssue(Builder $query, string|PartError $issue): void
    {
        $this->applyJsonConditionAcrossColumns($query, $issue, true, true);
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function applyHasItems(Builder $query, string $column): void
    {
        $query->whereJsonLength($column, '>', 0);
    }

    private function applyNoItems(Builder $query, string $column): void
    {
        $query->whereJsonLength($column, 0);
    }

    private function applyJsonCondition(
        Builder $query,
        string $column,
        string|PartError $error,
        bool $useOr = false,
        bool $negate = false
    ): void {
        $method = match (true) {
            $useOr && $negate => 'orWhereJsonDoesntContain',
            $useOr => 'orWhereJsonContains',
            $negate => 'whereJsonDoesntContain',
            default => 'whereJsonContains',
        };

        $value = $error instanceof PartError ? $error->value : $error;

        $query->{$method}($column, ['error' => $value]);
    }

    private function applyJsonConditionAcrossColumns(
        Builder $query,
        string|PartError $error,
        bool $useOr = false,
        bool $negate = false
    ): void {
        $value = $error instanceof PartError ? $error->value : $error;

        $query->where(function (Builder $q) use ($value, $useOr, $negate) {
            foreach ($this->errorAttributes as $attr) {
                $method = match (true) {
                    $useOr && $negate => 'orWhereJsonDoesntContain',
                    $useOr => 'orWhereJsonContains',
                    $negate => 'whereJsonDoesntContain',
                    default => 'whereJsonContains',
                };

                $q->{$method}($attr, ['error' => $value]);
            }
        });
    }
}
