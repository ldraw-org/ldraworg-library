<?php

namespace App\LDraw\Check;

use MCordingley\LinearAlgebra\Vector;

class VectorMath
{
    public static function hasColinearPoints(array $points): bool
    {
        $p1 = new Vector($points[0]);
        $p2 = new Vector($points[1]);
        $p3 = new Vector($points[2]);

        $v123 = $p1->subtractVector($p2)->dotProduct($p3->subtractVector($p2)) / ($p1->subtractVector($p2)->length() * $p3->subtractVector($p2)->length());
        $angles[] = rad2deg($v123 >= 1 ? 0 : ($v123 <= -1 ? Pi() : acos($v123)));

        if (count($points) == 3) {
            $v231 = $p2->subtractVector($p3)->dotProduct($p1->subtractVector($p3)) / ($p2->subtractVector($p3)->length() * $p1->subtractVector($p3)->length());
            $angles[] = rad2deg($v231 >= 1 ? 0 : ($v231 <= -1 ? Pi() : acos($v231)));
            $v312 = $p3->subtractVector($p1)->dotProduct($p2->subtractVector($p1)) / ($p3->subtractVector($p1)->length() * $p2->subtractVector($p1)->length());
            $angles[] = rad2deg($v312 >= 1 ? 0 : ($v312 <= -1 ? Pi() : acos($v312)));
        } else {
            $p4 = new Vector($points[3]);
            $v234 = $p2->subtractVector($p3)->dotProduct($p4->subtractVector($p3)) / ($p2->subtractVector($p3)->length() * $p4->subtractVector($p3)->length());
            $angles[] = rad2deg($v234 >= 1 ? 0 : ($v234 <= -1 ? Pi() : acos($v234)));
            $v341 = $p3->subtractVector($p4)->dotProduct($p1->subtractVector($p4)) / ($p3->subtractVector($p4)->length() * $p1->subtractVector($p4)->length());
            $angles[] = rad2deg($v341 >= 1 ? 0 : ($v341 <= -1 ? Pi() : acos($v341)));
            $v412 = $p4->subtractVector($p1)->dotProduct($p2->subtractVector($p1)) / ($p4->subtractVector($p1)->length() * $p2->subtractVector($p1)->length());
            $angles[] = rad2deg($v412 >= 1 ? 0 : ($v412 <= -1 ? Pi() : acos($v412)));
        }

        return max($angles) > config('ldraw.check.max_point_angle') || min($angles) < config('ldraw.check.min_point_angle');
    }

    public static function isConvex(array $points): bool
    {
        $p1 = new Vector($points[0]);
        $p2 = new Vector($points[1]);
        $p3 = new Vector($points[2]);
        $p4 = new Vector($points[3]);

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

        return !$concave && !$bowtie;
    }

    public static function maxCoplanerAngle(array $points): bool
    {
        $p1 = new Vector($points[0]);
        $p2 = new Vector($points[1]);
        $p3 = new Vector($points[2]);
        $p4 = new Vector($points[3]);

        $tri123_unorm = $p2->subtractVector($p1)->crossProduct($p3->subtractVector($p1))->normalize();
        $tri341_unorm = $p4->subtractVector($p3)->crossProduct($p1->subtractVector($p3))->normalize();
        $tri124_unorm = $p2->subtractVector($p1)->crossProduct($p4->subtractVector($p1))->normalize();
        $tri234_unorm = $p4->subtractVector($p3)->crossProduct($p2->subtractVector($p3))->normalize();

        $t1 = $tri123_unorm->dotProduct($tri341_unorm);
        $t2 = $tri124_unorm->dotProduct($tri234_unorm);
        $angle1 = rad2deg($t1 >= 1 ? 0 : ($t1 <= -1 ? Pi() : acos($t1)));
        $angle2 = rad2deg($t2 >= 1 ? 0 : ($t2 <= -1 ? Pi() : acos($t2)));

        return max($angle1, $angle2);
    }
}
