<?php

namespace App\Services\Check\Traits;

use App\Services\Check\Enums\CheckType;
use Illuminate\Support\Collection;

trait InteractsWithCheckMessages
{
    public function getErrors(): self
    {
        return $this->getCheckType(CheckType::Error);
    }

    public function getWarnings(): self
    {
        return $this->getCheckType(CheckType::Warning);
    }

    public function getTrackerHolds(): self
    {
        return $this->getCheckType(CheckType::TrackerHold);
    }

    protected function getCheckType(CheckType $checkType): self
    {
        return $this->filter(fn ($message)=> $message->check->type() == $checkType);
    }

    public function hasErrors(): bool
    {
        return $this->hasCheckType(CheckType::Error);
    }

    public function hasWarnings(): bool
    {
        return $this->hasCheckType(CheckType::Warning);
    }

    public function hasTrackerHolds(): bool
    {
        return $this->hasCheckType(CheckType::TrackerHold);
    }

    public function doesntHaveErrors(): bool
    {
        return !$this->hasErrors();
    }

    public function doesntHaveWarnings(): bool
    {
        return !$this->hasWarnings();
    }

    public function doesntHaveTrackerHolds(): bool
    {
        return !$this->hasTrackerHolds();
    }

    public function hasIssues(): bool
    {
        return $this->hasErrors() || $this->hasWarnings() || $this->hasTrackerHolds();
    }

    public function doesntHaveIssues(): bool
    {
        return !$this->hasIssues();
    }

    public function hasHoldableIssues(): bool
    {
        return $this->hasErrors() || $this->hasTrackerHolds();
    }

    public function doesntHaveHoldableIssues(): bool
    {
        return !$this->hasHoldableIssues();
    }

    protected function hasCheckType(CheckType $checkType): bool
    {
        return $this->contains(fn ($message) => $message->check->type() == $checkType);
    }

    public function messageArray(): Collection
    {
        return $this
            ->groupBy(fn ($m) => $m->check->value)
            ->map(fn ($messages) => [
                'check' => $check = $messages->first()->check,
                'message' => $messages->first()->message(),
                'lines' => $check->isMultiLine()
                    ? $messages->map(fn ($m) => "Line {$m->line_number}: {$m->text}")->values()->all()
                    : null,
            ])
            ->groupBy(fn (array $m) => $m['check']->type()->value)
            ->map(fn ($messages) => [
                'type' => $messages->first()['check']->type(),
                'checks' => $messages,
            ]);
    }
}
