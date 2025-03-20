<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class UnknownPartNumber implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $name = basename($part->filename);
        } else {
            $name = basename(str_replace('\\', '/', $part->name));
        }
        if (Str::startsWith($name, 'x')) {
            $fail(PartError::UnknownPartNumberName);
        }
    }
}
