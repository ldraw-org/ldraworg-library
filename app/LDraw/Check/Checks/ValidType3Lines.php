<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MCordingley\LinearAlgebra\Vector;

class ValidType3Lines implements Check
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
        $hasType3Lines = preg_match_all(config('ldraw.patterns.line_type_3'), $body, $matches, PREG_SET_ORDER);
        if (!$hasType3Lines) {
            return;
        }
        $max_angle = config('ldraw.check.max_point_angle');
        $min_angle = config('ldraw.check.min_point_angle');
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
                $fail(PartError::IndenticalPoints, ['value' => $lineNumber]);
                continue;
            }
            $p1 = new Vector($points[0]);
            $p2 = new Vector($points[1]);
            $p3 = new Vector($points[2]);
            $angle1 = rad2deg(acos($p1->subtractVector($p2)->dotProduct($p3->subtractVector($p2)) / ($p1->subtractVector($p2)->length() * $p3->subtractVector($p2)->length())));
            $angle2 = rad2deg(acos($p2->subtractVector($p3)->dotProduct($p1->subtractVector($p3)) / ($p2->subtractVector($p3)->length() * $p1->subtractVector($p3)->length())));
            $angle3 = rad2deg(acos($p3->subtractVector($p1)->dotProduct($p2->subtractVector($p1)) / ($p3->subtractVector($p1)->length() * $p2->subtractVector($p1)->length())));

            if (max($angle1, $angle2, $angle3) > $max_angle || min($angle1, $angle2, $angle3) < $min_angle) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
            }
        }
    }
}
