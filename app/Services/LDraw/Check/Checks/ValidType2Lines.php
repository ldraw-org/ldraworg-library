<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MathPHP\LinearAlgebra\MatrixFactory;

class ValidType2Lines implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $header_length = substr_count($part->header, "\n") + 2;
            $body = $part->body->body;
        } else {
            $header_length = $part->header_length;
            $body = $part->body;
        }
        $hasType2Lines = preg_match_all(config('ldraw.patterns.line_type_2'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType2Lines) {
            return;
        }
        foreach ($matches as $match) {
            $lineNumber = $header_length + substr_count(substr($body, 0, strpos($body, $match[0])), "\n");
            $points = MatrixFactory::create([
                [$match[2], $match[3], $match[4]],
                [$match[5], $match[6], $match[7]]
            ]);
            if ($points[0] == $points[1]) {
                $fail(PartError::IdenticalPoints, ['value' => $lineNumber]);
            }
        }
    }
}
