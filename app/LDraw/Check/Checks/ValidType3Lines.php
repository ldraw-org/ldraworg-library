<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Check\VectorMath;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class ValidType3Lines implements Check
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
        $hasType3Lines = preg_match_all(config('ldraw.patterns.line_type_3'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType3Lines) {
            return;
        }
        foreach ($matches as $match) {
            $lineNumber = $header_length + substr_count(substr($body, 0, strpos($body, $match[0])), "\n");
            $points = [
                [(float) $match[2], (float) $match[3], (float) $match[4]],
                [(float) $match[5], (float) $match[6], (float) $match[7]],
                [(float) $match[8], (float) $match[9], (float) $match[10]],
            ];
            if ($points[0] == $points[1] ||
                $points[1] == $points[2] ||
                $points[2] == $points[0]
            ) {
                $fail(PartError::IdenticalPoints, ['value' => $lineNumber]);
                continue;
            }
            if (VectorMath::hasColinearPoints($points)) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
            }
        }
    }
}
