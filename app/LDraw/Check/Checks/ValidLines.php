<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Str;

class ValidLines implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $header_length = count(explode("\n", $part->header)) + 2;
            $body = explode("\n", $part->body->body);
        } else {
            $header_length = $part->header_length;
            $body = explode("\n", $part->body);
        }
        foreach ($body as $index => $line) {
            $line = Str::squish($line);
            if (empty($line)) {
                continue;
            }
            if (is_null(config('ldraw.patterns.line_type_' . $line[0])) ||
                ! preg_match(config('ldraw.patterns.line_type_' . $line[0]), $line, $matches)
            ) {
                $fail(PartError::LineInvalid, ['value' => $index + $header_length]);
            }
        }
    }
}
