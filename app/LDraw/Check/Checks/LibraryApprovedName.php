<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class LibraryApprovedName implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $name = $part->name();
        } else {
            $name = $part->name;
        }
        if (! preg_match(config('ldraw.patterns.library_approved_name'), $name, $matches)) {
            $fail(PartError::PartNameInvalid);
        }
    }
}
