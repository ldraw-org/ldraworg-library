<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class LibraryApprovedDescription implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if (! preg_match(config('ldraw.patterns.library_approved_description'), $part->description, $matches)) {
            $fail(PartError::InvalidDescription);
        }
    }
}
