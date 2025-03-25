<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MCordingley\LinearAlgebra\Vector;

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

        $max_angle = config('ldraw.check.max_point_angle');
        $min_angle = config('ldraw.check.min_point_angle');
        $max_coplaner_angle = config('ldraw.check.coplaner_angle');

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
            $p1 = new Vector($points[0]);
            $p2 = new Vector($points[1]);
            $p3 = new Vector($points[2]);
            $p4 = new Vector($points[3]);

            $angle1 = rad2deg(acos($p1->subtractVector($p2)->dotProduct($p3->subtractVector($p2)) / ($p1->subtractVector($p2)->length() * $p3->subtractVector($p2)->length())));
            $angle2 = rad2deg(acos($p2->subtractVector($p3)->dotProduct($p4->subtractVector($p3)) / ($p2->subtractVector($p3)->length() * $p4->subtractVector($p3)->length())));
            $angle3 = rad2deg(acos($p3->subtractVector($p4)->dotProduct($p1->subtractVector($p4)) / ($p3->subtractVector($p4)->length() * $p1->subtractVector($p4)->length())));
            $angle4 = rad2deg(acos($p4->subtractVector($p1)->dotProduct($p2->subtractVector($p1)) / ($p4->subtractVector($p1)->length() * $p2->subtractVector($p1)->length())));

            if (max($angle1, $angle2, $angle3, $angle4) > $max_angle || min($angle1, $angle2, $angle3, $angle4) < $min_angle) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
                continue;
            }

            $v01 = $p2->subtractVector($p1);
            $v02 = $p3->subtractVector($p1);
            $v03 = $p4->subtractVector($p1);
            $v12 = $p3->subtractVector($p2);
            $v13 = $p4->subtractVector($p2);
            $v23 = $p4->subtractVector($p3);

            $a = $v01->crossProduct($v02)->dotProduct($v02->crossProduct($v03)) > 0;
            $b = $v12->crossProduct($v01)->dotProduct($v01->crossProduct($v13)) > 0;
            $c = -$v02->crossProduct($v12)->dotProduct($v12->crossProduct($v23)) > 0;
            $concave = ($a && (($b && !$c) || ($c && !$b))) || (!$a && (($b && $c) || (!$b && !$c)));
            $bowtie = (!$a && $b && !$c) || (!$a && !$b && $c);
            if ($concave || $bowtie) {
                $fail(PartError::QuadNotConvex, ['value' => $lineNumber]);
                continue;
            }

            $tri123_unorm = $p2->subtractVector($p1)->crossProduct($p3->subtractVector($p1))->normalize();
            $tri341_unorm = $p4->subtractVector($p3)->crossProduct($p1->subtractVector($p3))->normalize();
            $tri124_unorm = $p2->subtractVector($p1)->crossProduct($p4->subtractVector($p1))->normalize();
            $tri234_unorm = $p4->subtractVector($p3)->crossProduct($p2->subtractVector($p3))->normalize();
            $angle = max(rad2deg(acos($tri123_unorm->dotProduct($tri341_unorm))), rad2deg(acos($tri124_unorm->dotProduct($tri234_unorm))));

            if ($angle > $max_coplaner_angle) {
                $fail(PartError::QuadNotCoplanar, ['value' => $lineNumber]);
            }

        }
    }

}
