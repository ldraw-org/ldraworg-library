<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Check\VectorMath;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;

class ValidType4Lines implements Check
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
        $hasType4Lines = preg_match_all(config('ldraw.patterns.line_type_4'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType4Lines) {
            return;
        }

        foreach ($matches as $match) {
            $lineNumber = $header_length + substr_count(substr($body, 0, strpos($body, $match[0])), "\n");
            $points = [
                [(float) $match[2], (float) $match[3], (float) $match[4]],
                [(float) $match[5], (float) $match[6], (float) $match[7]],
                [(float) $match[8], (float) $match[9], (float) $match[10]],
                [(float) $match[11], (float) $match[12], (float) $match[13]],
            ];
            if ($points[0] == $points[1] ||
                $points[1] == $points[2] ||
                $points[2] == $points[3] ||
                $points[3] == $points[0] ||
                $points[0] == $points[2] ||
                $points[3] == $points[1]
            ) {
                $fail(PartError::IndenticalPoints, ['value' => $lineNumber]);
                continue;
            }

            if (VectorMath::hasColinearPoints($points)) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
                continue;
            }

            if (!VectorMath::isConvex($points)) {
                $fail(PartError::QuadNotConvex, ['value' => $lineNumber]);
                continue;
            }

            $angle = VectorMath::maxCoplanarAngle($points);
            if ($angle > config('ldraw.check.coplanar_angle_error')) {
                $fail(PartError::QuadNotCoplanar, ['value' => $lineNumber, 'angle' => $angle]);
            } elseif ($angle > config('ldraw.check.coplanar_angle_warning')) {
                $fail(PartError::WarningNotCoplanar, ['value' => $lineNumber, 'angle' => $angle]);
            }

        }
    }

}
