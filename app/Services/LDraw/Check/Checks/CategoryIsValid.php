<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class CategoryIsValid implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            $part->type?->inPartsFolder() &&
            is_null($part->metaCategory) &&
            is_null($part->descriptionCategory)
        ) {
            $fail(PartError::CategoryInvalid);
        }
    }
}
