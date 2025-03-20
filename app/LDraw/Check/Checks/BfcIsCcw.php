<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class BfcIsCcw implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part->bfc !== 'CCW') {
            $fail(PartError::BfcNotCcw);
        }
    }
}
