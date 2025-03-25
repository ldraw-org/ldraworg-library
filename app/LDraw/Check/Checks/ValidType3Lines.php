<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;

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
            $points = MatrixFactory::create([
                [$match[2], $match[3], $match[4]],
                [$match[5], $match[6], $match[7]],
                [$match[8], $match[9], $match[10]],
            ]);
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
            $angle1 = $p1->subtract($p2)->angleBetween($p3->subtract($p2), true);
            $angle2 = $p2->subtract($p3)->angleBetween($p1->subtract($p3), true);
            $angle3 = $p3->subtract($p1)->angleBetween($p2->subtract($p1), true);

            if (max($angle1, $angle2, $angle3) > $max_angle || min($angle1, $angle2, $angle3) < $min_angle) {
                $fail(PartError::PointsColinear, ['value' => $lineNumber]);
            }
        }
    }
}
