<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\LdrawColour;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ValidLines implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $header_length = substr_count($part->header, "\n") + 2;
            $body = explode("\n", $part->body->body);
        } else {
            $header_length = $part->header_length;
            $body = explode("\n", $part->body);
        }
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        foreach ($body as $index => $line) {
            $line = Str::squish($line);
            if (empty($line)) {
                continue;
            }
            if (is_null(config('ldraw.patterns.line_type_' . $line[0])) ||
                ! preg_match(config('ldraw.patterns.line_type_' . $line[0]), $line, $matches)
            ) {
                $fail(PartError::LineInvalid, ['value' => $index + $header_length]);
                continue;
            }
            if ($line[0] == '0') {
                continue;
            }
            switch ($line[0]) {
                case '1':
                case '4':
                case '5':
                    $numbers = [$matches[2], $matches[3], $matches[4], $matches[5], $matches[6], $matches[7], $matches[8], $matches[9], $matches[10], $matches[11], $matches[12], $matches[13]];
                    break;
                case '2':
                    $numbers = [$matches[2], $matches[3], $matches[4], $matches[5], $matches[6], $matches[7]];
                    break;
                case '3':
                    $numbers = [$matches[2], $matches[3], $matches[4], $matches[5], $matches[6], $matches[7], $matches[8], $matches[9], $matches[10]];
                    break;
            }
            if (count(Arr::reject($numbers, fn (string $value, int $key) => !is_numeric($value))) != count($numbers)) {
                $fail(PartError::InvalidLineNumbers, ['value' => $index + $header_length]);
                continue;
            }
            if (!Str::of($matches['color'])->startsWith('0x') && !in_array($matches['color'], $codes)) {
                $fail(PartError::InvalidLineColor, ['value' => $index + $header_length]);
                continue;
            }
            if ($matches['color'] == '24' && in_array($line[0], ['1','3','4'])) {
                $fail(PartError::InvalidColor24, ['value' => $index + $header_length]);
                continue;
            }
            if ($matches['color'] == '16' && in_array($line[0], ['2','5'])) {
                $fail(PartError::InvalidColor16, ['value' => $index + $header_length]);
            }

        }
    }
}
