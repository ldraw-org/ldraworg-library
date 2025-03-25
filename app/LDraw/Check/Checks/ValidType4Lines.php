<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;

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
            $points = MatrixFactory::create([
                [$match[2], $match[3], $match[4]],
                [$match[5], $match[6], $match[7]],
                [$match[8], $match[9], $match[10]],
                [$match[11], $match[12], $match[13]],
            ]);
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

            $angle1 = $p1->subtract($p2)->angleBetween($p3->subtract($p2), true);
            $angle2 = $p2->subtract($p3)->angleBetween($p4->subtract($p3), true);
            $angle3 = $p3->subtract($p4)->angleBetween($p1->subtract($p4), true);
            $angle4 = $p4->subtract($p1)->angleBetween($p2->subtract($p1), true);

            if (max($angle1, $angle2, $angle3, $angle4) > $max_angle || min($angle1, $angle2, $angle3, $angle4) < $min_angle) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
                continue;
            }

            $v01 = $p2->subtract($p1);
            $v02 = $p3->subtract($p1);
            $v03 = $p4->subtract($p1);
            $v12 = $p3->subtract($p2);
            $v13 = $p4->subtract($p2);
            $v23 = $p4->subtract($p3);

            $a = $v01->crossProduct($v02)->dotProduct($v02->crossProduct($v03)) > 0;
            $b = $v12->crossProduct($v01)->dotProduct($v01->crossProduct($v13)) > 0;
            $c = -$v02->crossProduct($v12)->dotProduct($v12->crossProduct($v23)) > 0;
            $concave = ($a && (($b && !$c) || ($c && !$b))) || (!$a && (($b && $c) || (!$b && !$c)));
            $bowtie = (!$a && $b && !$c) || (!$a && !$b && $c);
            if ($concave || $bowtie) {
                $fail(PartError::QuadNotConvex, ['value' => $lineNumber]);
                continue;
            }

            $tri123_unorm = $p2->subtract($p1)->crossProduct($p3->subtract($p1))->normalize();
            $tri341_unorm = $p4->subtract($p3)->crossProduct($p1->subtract($p3))->normalize();
            $tri124_unorm = $p2->subtract($p1)->crossProduct($p4->subtract($p1))->normalize();
            $tri234_unorm = $p4->subtract($p3)->crossProduct($p2->subtract($p3))->normalize();
            $angle = max(rad2deg(acos($tri123_unorm->dotProduct($tri341_unorm))), rad2deg(acos($tri124_unorm->dotProduct($tri234_unorm))));

            if ($angle > $max_coplaner_angle) {
                $fail(PartError::QuadNotCoplanar, ['value' => $lineNumber]);
            }

        }
    }

}
