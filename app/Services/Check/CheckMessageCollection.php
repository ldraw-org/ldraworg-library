<?php

namespace App\Services\Check;

use App\Enums\CheckType;
use Illuminate\Support\Collection;

class CheckMessageCollection extends Collection
{
    public static function fromArray(array $messages): self
    {
        return new self(
            array_map(
                fn($message) => $message instanceof CheckMessage 
                    ? $message 
                    : CheckMessage::fromArray($message),
                $messages
            )
        );
    }

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
        return $this->filter(fn (CheckMessage $message)=> $message->checkType == $checkType);
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
        return $this->contains(fn (CheckMessage $message)=> $message->checkType == $checkType);
    }

}