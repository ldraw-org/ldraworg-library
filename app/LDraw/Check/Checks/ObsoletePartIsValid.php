<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartType;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class ObsoletePartIsValid implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->type->folder() != 'parts') {
            return;
        }

        $desc = Str::of($part->description);
        $descObsolete = $desc->contains('(Obsolete)') || $desc->startsWith('~Obsolete');

        if ($part instanceof ParsedPart) {
            $catObsolete = $part->metaCategory == PartCategory::Obsolete;
        }
        else {
            $catObsolete = $part->category ==  PartCategory::Obsolete;
        }

        if (($catObsolete && !$descObsolete) || (!$catObsolete && $descObsolete)) {
            $fail(PartError::ImproperObsolete);
        }
    }
}
