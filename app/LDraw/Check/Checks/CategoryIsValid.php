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
            !is_null($part->metaCategory) &&
            is_null(PartCategory::firstWhere('category', $part->metaCategory))
        ) {
            $fail(__('partcheck.category.invalid', ['value' => $part->metaCategory]));
        } elseif ($part instanceof ParsedPart &&
            (is_null($part->descriptionCategory) ||
            is_null(PartCategory::firstWhere('category', $part->descriptionCategory)))
        ) {
            $fail(__('partcheck.category.invalid', ['value' => $part->descriptionCategory]));
        }
    }
}
