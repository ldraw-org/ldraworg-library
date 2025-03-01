<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class FlexibleSectionIsPart implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->type_qualifier == PartTypeQualifier::FlexibleSection &&
            $part->type != PartType::Part
        ) {
            $fail(__('partcheck.type.flex'));
        }
    }
}
