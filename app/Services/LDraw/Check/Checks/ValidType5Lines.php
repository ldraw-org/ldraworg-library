<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MathPHP\LinearAlgebra\MatrixFactory;

class ValidType5Lines implements Check
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
        $hasType5Lines = preg_match_all(config('ldraw.patterns.line_type_5'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType5Lines) {
            return;
        }
        foreach ($matches as $match) {
            $lineNumber = $header_length + substr_count(substr($body, 0, strpos($body, $match[0])), "\n");
            $points = MatrixFactory::create([
                [$match[2], $match[3], $match[4]],
                [$match[5], $match[6], $match[7]],
                [$match[8], $match[9], $match[10]],
                [$match[11], $match[12], $match[13]],
            ]);
            if ($points[0] == $points[1] ||
                $points[2] == $points[3]) {
                $fail(PartError::IdenticalPoints, ['value' => $lineNumber]);
            }
        }
    }

}
