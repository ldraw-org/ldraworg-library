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
        if ($part instanceof ParsedPart && $part->type?->inPartsFolder()) {
            $pcat = $part->metaCategory ?? $part->descriptionCategory;
            $pcat = PartCategory::firstWhere('category', $pcat);
            if (is_null($pcat)) {
                $fail(__('partcheck.category.invalid', ['value' => $pcat]));
            }
        }
    }
}
