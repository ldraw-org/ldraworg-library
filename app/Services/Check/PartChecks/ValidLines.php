<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\VectorMath;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Models\LdrawColour;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MathPHP\LinearAlgebra\MatrixFactory;

class ValidLines implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        
        if ($part->hasInvalidLines()) {
            $part->where('invalid', true)
                ->sortby('line_number')
                ->each(fn (array $line) => $message(error: PartError::LineInvalid, lineNumber: $line['line_number']));
        }
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        $vector = new VectorMath();
        $part->whereIn('linetype', ['1', '2', '3', '4', '5'])
            ->where('invalid', false)
            ->sortby('line_number')
            ->each(function (array $line) use ($codes, $message, $vector) {
                if (Str::doesntStartWith($line['color'], '0x') && !in_array($line['color'], $codes)) {
                    $message(error: PartError::InvalidLineColor, lineNumber: $line['line_number']);
                }
                if ($line['color'] == '24' && in_array($line['linetype'], ['1','3','4'])) {
                    $message(error: PartError::InvalidColor24, lineNumber: $line['line_number']);
                }
                if ($line['color'] == '16' && in_array($line['linetype'], ['2','5'])) {
                    $message(error: PartError::InvalidColor16, lineNumber: $line['line_number']);
                }
                switch ($line['linetype']) {
                    case '1':
                        $matrix = MatrixFactory::create([
                            [$line['a'], $line['b'], $line['c'], $line['x1']],
                            [$line['d'], $line['e'], $line['f'], $line['y1']],
                            [$line['g'], $line['h'], $line['i'], $line['z1']],
                            [0, 0, 0, 1],
                        ]);
                        if ($matrix->isSingular()) {
                            $message(error: PartError::RotationMatrixIsSingular, lineNumber: $line['line_number']);
                        }
                        break;
                    case '2':
                        $points = MatrixFactory::create([
                            [$line['x1'], $line['y1'], $line['z1']],
                            [$line['x2'], $line['y2'], $line['z2']]
                        ]);
                        if ($points[0] == $points[1]) {
                            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
                        }
                        break;
                    case '3':
                        $points = [
                            [(float) $line['x1'], (float) $line['y1'], (float) $line['z1']],
                            [(float) $line['x2'], (float) $line['y2'], (float) $line['z2']],
                            [(float) $line['x3'], (float) $line['y3'], (float) $line['z3']],
                        ];
                        if ($points[0] == $points[1] ||
                            $points[1] == $points[2] ||
                            $points[2] == $points[0]
                        ) {
                            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
                            return;
                        }
                        $angle = $vector->hasColinearPoints($points);
                        if ($angle !== false) {
                            $message(error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle);
                        }
                        break;
                    case '4':
                        $points = [
                            [(float) $line['x1'], (float) $line['y1'], (float) $line['z1']],
                            [(float) $line['x2'], (float) $line['y2'], (float) $line['z2']],
                            [(float) $line['x3'], (float) $line['y3'], (float) $line['z3']],
                            [(float) $line['x4'], (float) $line['y4'], (float) $line['z4']],
                        ];
                        if ($points[0] == $points[1] ||
                            $points[1] == $points[2] ||
                            $points[2] == $points[3] ||
                            $points[3] == $points[0] ||
                            $points[0] == $points[2] ||
                            $points[3] == $points[1]
                        ) {
                            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
                            return;
                        }
                        $angle = $vector->hasColinearPoints($points);
                        if ($angle !== false) {
                            $message(error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle);
                            return;
                        }
            
                        if (!$vector->isConvexQuad($points)) {
                            $message(error: PartError::QuadNotConvex, lineNumber: $line['line_number']);
                            return;
                        }
            
                        $angle = $vector->getMaxCoplanarAngle($points);
                        if ($angle > config('ldraw.check.coplanar_angle_error')) {
                            $message(error: PartError::QuadNotCoplanar, lineNumber: $line['line_number'], value: $angle);
                        } elseif ($angle > config('ldraw.check.coplanar_angle_warning')) {
                            // $message(error: PartError::WarningNotCoplanar, lineNumber: $line['line_number'], value: $angle);
                        }
                        break;
                    case '5':
                        $points = MatrixFactory::create([
                            [$line['x1'], $line['y1'], $line['z1']],
                            [$line['x2'], $line['y2'], $line['z2']],
                            [$line['x3'], $line['y3'], $line['z3']],
                            [$line['x4'], $line['y4'], $line['z4']]
                        ]);
                        if ($points[0] == $points[1] ||
                            $points[2] == $points[3]) {
                            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
                        }
                        break;
                }
            });
        
    }
}
