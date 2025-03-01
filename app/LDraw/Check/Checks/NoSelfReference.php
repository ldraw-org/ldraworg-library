<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class NoSelfReference implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $selfref = $part->subparts->where('filename', $part->filename)->count() > 0;
        } else {
            $selfref = in_array($part->name, $part->subparts['subparts'] ?? []);
        }
        if ($selfref) {
            $fail(__('partcheck.selfreference'));
        }
    }
}
