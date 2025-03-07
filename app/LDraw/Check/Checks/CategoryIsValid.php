<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\Part\PartCategory;
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
            $fail(__('partcheck.category.invalid'));
        }
    }
}
