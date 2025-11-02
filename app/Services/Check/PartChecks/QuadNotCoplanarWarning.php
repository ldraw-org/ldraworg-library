<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Services\VectorMath;
use Closure;

class QuadNotCoplanarWarning implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        $vector = new VectorMath();
        $part->where('linetype', '4')
            ->each(function (array $line) use ($vector, $message) {
                $points = [
                    [(float) $line['x1'], (float) $line['y1'], (float) $line['z1']],
                    [(float) $line['x2'], (float) $line['y2'], (float) $line['z2']],
                    [(float) $line['x3'], (float) $line['y3'], (float) $line['z3']],
                    [(float) $line['x4'], (float) $line['y4'], (float) $line['z4']],
                ];
                if ($vector->hasColinearPoints($points) !== false) {
                    return;
                }
                $angle = $vector->getMaxCoplanarAngle($points);
                if ($angle >= config('ldraw.check.coplanar_angle_warning') && $angle < config('ldraw.check.coplanar_angle_error')) {
                    $message(error: PartError::WarningNotCoplanar, lineNumber: $line['line_number'], value: $angle);
                }
            });
    }
}
