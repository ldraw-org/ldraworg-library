<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;

class FlexibleHasCorrectSuffix extends BaseCheck
{
    public function check(): iterable
    {
        $hasKSuffix = $this->part->lastSuffixStartsWith('k');
        $category = $this->part->category();
        if ($this->part->type_qualifier() == PartTypeQualifier::FlexibleSection && $category?->isActive() &&
            !$hasKSuffix
        ) {
            yield $this->error(CheckType::Error, PartError::FlexSectionIncorrectSuffix);
        }
    }
}
