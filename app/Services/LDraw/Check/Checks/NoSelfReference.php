<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class NoSelfReference implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            in_array($part->name, $part->subparts['subparts'] ?? [])
        ) {
            $fail(PartError::CircularReference);
        }
    }
}
