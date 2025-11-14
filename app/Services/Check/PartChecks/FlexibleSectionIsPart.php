<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class FlexibleSectionIsPart extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type_qualifier() == PartTypeQualifier::FlexibleSection &&
            $this->part->type() != PartType::Part
        ) {
            yield $this->error(CheckType::Error, PartError::FlexSectionNotPart);
        }
    }
}
