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
    protected VectorMath $vector;

    public function __construct()
    {
        $this->vector = new VectorMath();
    }

    public function check(ParsedPartCollection $part, Closure $message): void
    {
        
        if ($part->hasInvalidLines()) {
            $part->where('invalid', true)
                ->sortby('line_number')
                ->each(fn (array $line) => $message(error: PartError::LineInvalid, lineNumber: $line['line_number'], text: $line['text']));
        }
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        $part->whereIn('linetype', ['0','1', '2', '3', '4', '5'])
            ->where('invalid', false)
            ->sortby('line_number')
            ->each(function (array $line) use ($codes, $message) {
                if ($line['linetype'] == 0 && Arr::get($line, 'meta') != 'texmap_geometry') {
                    return;
                } elseif ($line['linetype'] == 0 && Arr::get($line, 'meta') == 'texmap_geometry') {
                    $line['line']['text'] = $line['text'];
                    $line['line']['line_number'] = $line['line_number'];
                    $line = $line['line'];
                }
                if (Str::doesntStartWith($line['color'], '0x') && !in_array($line['color'], $codes)) {
                    $message(error: PartError::InvalidLineColor, lineNumber: $line['line_number'], text: $line['text']);
                }
                switch ($line['linetype']) {
                    case '1':
                        $this->checkLineType1($line, $message);
                        break;
                    case '2':
                        $this->checkLineType2($line, $message);
                        break;
                    case '3':
                        $this->checkLineType3($line, $message);
                        break;
                    case '4':
                        $this->checkLineType4($line, $message);
                        break;
                    case '5':
                        $this->checkLineType5($line, $message);
                        break;
                }
            });
        
    }

    protected function checkLineType1(array $line, \Closure $message): void
    {
        if ($line['color'] == '24') {
            $message(error: PartError::InvalidColor24, lineNumber: $line['line_number'], text: $line['text']);
        }
        $matrix = MatrixFactory::create([
            [$line['a'], $line['b'], $line['c'], $line['x1']],
            [$line['d'], $line['e'], $line['f'], $line['y1']],
            [$line['g'], $line['h'], $line['i'], $line['z1']],
            [0, 0, 0, 1],
        ]);
        if ($matrix->isSingular()) {
            $message(error: PartError::RotationMatrixIsSingular, lineNumber: $line['line_number'], text: $line['text']);
        }
    }

    protected function checkLineType2(array $line, \Closure $message): void
    {
        if ($line['color'] == '15') {
            $message(error: PartError::InvalidColor16, lineNumber: $line['line_number'], text: $line['text']);
        }
        $points = MatrixFactory::create([
            [$line['x1'], $line['y1'], $line['z1']],
            [$line['x2'], $line['y2'], $line['z2']]
        ]);
        if ($points[0] == $points[1]) {
            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
        }
    }

    protected function checkLineType3(array $line, \Closure $message): void
    {
        if ($line['color'] == '24') {
            $message(error: PartError::InvalidColor24, lineNumber: $line['line_number'], text: $line['text']);
        }
        $points = [
            [(float) $line['x1'], (float) $line['y1'], (float) $line['z1']],
            [(float) $line['x2'], (float) $line['y2'], (float) $line['z2']],
            [(float) $line['x3'], (float) $line['y3'], (float) $line['z3']],
        ];
        if ($points[0] == $points[1] ||
            $points[1] == $points[2] ||
            $points[2] == $points[0]
        ) {
            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
            return;
        }
        $angle = $this->vector->hasColinearPoints($points);
        if ($angle !== false) {
            $message(error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
        }
    }

    protected function checkLineType4(array $line, \Closure $message): void
    {
        if ($line['color'] == '24') {
            $message(error: PartError::InvalidColor24, lineNumber: $line['line_number'], text: $line['text']);
        }
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
            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
            return;
        }
        $angle = $this->vector->hasColinearPoints($points);
        if ($angle !== false) {
            $message(error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
            return;
        }

        if (!$this->vector->isConvexQuad($points)) {
            $message(error: PartError::QuadNotConvex, lineNumber: $line['line_number'], text: $line['text']);
            return;
        }

        $angle = $this->vector->getMaxCoplanarAngle($points);
        if ($angle > config('ldraw.check.coplanar_angle_error')) {
            $message(error: PartError::QuadNotCoplanar, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
        }
    }

    protected function checkLineType5(array $line, \Closure $message): void
    {
        if ($line['color'] == '16') {
            $message(error: PartError::InvalidColor16, lineNumber: $line['line_number'], text: $line['text']);
        }
        $points = MatrixFactory::create([
            [$line['x1'], $line['y1'], $line['z1']],
            [$line['x2'], $line['y2'], $line['z2']],
            [$line['x3'], $line['y3'], $line['z3']],
            [$line['x4'], $line['y4'], $line['z4']]
        ]);
        if ($points[0] == $points[1] ||
            $points[2] == $points[3]) {
            $message(error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
        }
    }

}
