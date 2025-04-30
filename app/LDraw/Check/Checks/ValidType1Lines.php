<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MathPHP\LinearAlgebra\MatrixFactory;

class ValidType1Lines implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof Part) {
            $header_length = count(explode("\n", $part->header)) + 2;
            $body = $part->body->body;
        } else {
            $header_length = $part->header_length;
            $body = $part->body;
        }
        $hasType1Lines = preg_match_all(config('ldraw.patterns.line_type_1'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType1Lines) {
            return;
        }
        foreach ($matches as $match) {
            $lineNumber = $header_length + substr_count(substr($body, 0, strpos($body, $match[0])), "\n");
            $matrix = MatrixFactory::create([
                [$match[5], $match[6], $match[7], $match[2]],
                [$match[8], $match[9], $match[10], $match[3]],
                [$match[11], $match[12], $match[13], $match[4]],
                [0, 0, 0, 1],
            ]);
            if ($matrix->isSingular()) {
                $fail(PartError::RotationMatrixIsSingular, ['value' => $lineNumber]);
            }
        }
    }
}
