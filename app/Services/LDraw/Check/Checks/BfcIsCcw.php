<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
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
