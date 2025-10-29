<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Arr;

class FlexibleHasCorrectSuffix implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $hasKSuffix = $part->lastSuffixStartsWith('k');
        $category = $part->category();
        if ($part->type_qualifier() == PartTypeQualifier::FlexibleSection && $category?->isActive() &&
            !$hasKSuffix
        ) {
            $message(PartError::FlexSectionIncorrectSuffix);
        }
    }
}
