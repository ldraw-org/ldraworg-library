<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class AliasInParts implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->type_qualifier == PartTypeQualifier::Alias &&
            ! $part->type->inPartsFolder()
        ) {
            $fail(PartError::AliasNotInParts);
        }
    }
}
