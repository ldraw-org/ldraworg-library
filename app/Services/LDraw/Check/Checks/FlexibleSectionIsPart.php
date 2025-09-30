<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class FlexibleSectionIsPart implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->type_qualifier == PartTypeQualifier::FlexibleSection &&
            $part->type != PartType::Part
        ) {
            $fail(PartError::FlexSectionNotPart);
        }
    }
}
