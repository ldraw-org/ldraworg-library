<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class NewPartNotPhysicalColor implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            $part->type_qualifier == PartTypeQualifier::PhysicalColour
        ) {
            $fail(PartError::NewPartIsPhysicalColor);
        }
    }
}
