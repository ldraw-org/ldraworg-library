<?php

namespace App\Services\Check\Traits;

use App\Enums\CheckType;
use App\Services\Check\CheckMessage;
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
        return $this->filter(fn (CheckMessage $message)=> $message->check_type == $checkType);
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
        return $this->contains(fn ($message)=> $message->check_type == $checkType);
    }

    public function arrayByType(): Collection
    {
        return $this
            ->map(fn ($m) => [
                'check_type' => $m->check_type->value,
                'error' => $m->check->value,
                'message' => $m->message(),
                'line_number' => $m->line_number,
                'text' => $m->text,
            ])
            ->groupBy(['check_type', 'error']);
    }

}
