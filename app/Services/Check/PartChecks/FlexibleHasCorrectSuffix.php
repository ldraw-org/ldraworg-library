<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class FlexibleHasCorrectSuffix extends BaseCheck
{
    public function check(): iterable
    {
        $hasKSuffix = $this->part->lastSuffixStartsWith('k');
        $category = $this->part->category();
        if ($this->part->type_qualifier() == PartTypeQualifier::FlexibleSection && $category?->isActive() &&
            !$hasKSuffix
        ) {
            yield $this->error(PartError::FlexSectionIncorrectSuffix);
        }
    }
}
