<?php

namespace App\Services;

use MathPHP\LinearAlgebra\Vector;

class VectorMath
{
    public function triangleNormal(array $triangle): Vector
    {
        [$a, $b, $c] = $triangle;
        $a = new Vector($a);
        $b = new Vector($b);
        $c = new Vector($c);
    
        return $a->subtract($b)
                 ->crossProduct($c->subtract($a))
                 ->normalize();      
    }

    public function angleBetweenTriangles(array $triangle1, array $triangle2): float
    {
        $n1 = $this->triangleNormal($triangle1);
        $n2 = $this->triangleNormal($triangle2);
        return $n1->angleBetween($n2, inDegrees: true);
    }

    public function angleAtVertex(array $p1, array $p2, array $p3): float
    {
        $v1 = (new Vector($p1))->subtract(new Vector($p2));
        $v2 = (new Vector($p3))->subtract(new Vector($p2));

        return $v1->angleBetween($v2, inDegrees: true);
    }

    public function isConvexQuad(array $quad): bool
    {
        [$p1, $p2, $p3, $p4] = $quad;
    
        $v1 = (new Vector($p2))->subtract(new Vector($p1));
        $v2 = (new Vector($p3))->subtract(new Vector($p2));
        $v3 = (new Vector($p4))->subtract(new Vector($p3));
        $v4 = (new Vector($p1))->subtract(new Vector($p4));
    
        $c1 = $v1->crossProduct($v2)->normalize();
        $c2 = $v2->crossProduct($v3)->normalize();
        $c3 = $v3->crossProduct($v4)->normalize();
        $c4 = $v4->crossProduct($v1)->normalize();
    
        $dot12 = $c1->dotProduct($c2);
        $dot23 = $c2->dotProduct($c3);
        $dot34 = $c3->dotProduct($c4);
        $dot41 = $c4->dotProduct($c1);
    
        return $dot12 > 0 && $dot23 > 0 && $dot34 > 0 && $dot41 > 0;
    }

    public function hasColinearPoints(array $points): float|false
    {
        $angles = [];

        $angles[] = $this->angleAtVertex($points[0], $points[1], $points[2]);

        if (count($points) == 3) {
            $angles[] = $this->angleAtVertex($points[1], $points[2], $points[0]);
            $angles[] = $this->angleAtVertex($points[2], $points[0], $points[1]);
        } else {
            $angles[] = $this->angleAtVertex($points[1], $points[2], $points[3]);
            $angles[] = $this->angleAtVertex($points[2], $points[3], $points[0]);
            $angles[] = $this->angleAtVertex($points[3], $points[0], $points[1]);          
        }

        if (max($angles) > config('ldraw.check.max_point_angle')) {
            return max($angles);
        } elseif (min($angles) < config('ldraw.check.min_point_angle')) {
            return min($angles);
        }

        return false;
    }

    public function getMaxCoplanarAngle(array $points): float
    {
        $tri11 = [$points[0], $points[1], $points[3]];
        $tri12 = [$points[1], $points[2], $points[3]];
        $tri21 = [$points[0], $points[2], $points[3]];
        $tri22 = [$points[0], $points[1], $points[2]];
        return max($this->angleBetweenTriangles($tri11, $tri12), $this->angleBetweenTriangles($tri21, $tri22));
    }
}
