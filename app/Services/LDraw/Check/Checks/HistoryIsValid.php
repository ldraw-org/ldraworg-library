<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class HistoryIsValid implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart) {
            $hcount = count($part->history ?? []);
            if ($hcount != mb_substr_count($part->rawText, '!HISTORY')) {
                $fail(PartError::HistoryInvalid);
            }
        }
    }
}
