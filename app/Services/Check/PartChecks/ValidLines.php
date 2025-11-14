<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\VectorMath;
use App\Services\Check\BaseCheck;
use App\Models\LdrawColour;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MathPHP\LinearAlgebra\MatrixFactory;

class ValidLines extends BaseCheck
{

    public function __construct(
        protected VectorMath $vector
    )
    {}

    public function check(): iterable
    {
        
        foreach($this->part->invalidLines() as $line) {
             yield $this->error(CheckType::Error, error: PartError::LineInvalid, lineNumber: $line['line_number'], text: $line['text']);
        }

        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        foreach ($this->part->bodyLines()->whereIn('linetype', [0,1,2,3,4,5]) as $line) {
            if ($line['linetype'] == 0 && Arr::get($line, 'meta') != 'texmap_geometry') {
                continue;
            } elseif ($line['linetype'] == 0 && Arr::get($line, 'meta') == 'texmap_geometry') {
                $line['line']['text'] = $line['text'];
                $line['line']['line_number'] = $line['line_number'];
                $line = $line['line'];
            }
            try {
            if (Str::doesntStartWith($line['color'], '0x') && !in_array($line['color'], $codes)) {
                yield $this->error(CheckType::Error, error: PartError::InvalidLineColor, lineNumber: $line['line_number'], text: $line['text']);
            }
        } catch (\Exception $e) { dd($line); }
            switch ($line['linetype']) {
                case '1':
                    if ($line['color'] == '24') {
                        yield $this->error(CheckType::Error, error: PartError::InvalidLineColor, lineNumber: $line['line_number'], text: $line['text']);
                    }
                    $matrix = MatrixFactory::create([
                        [$line['a'], $line['b'], $line['c'], $line['x1']],
                        [$line['d'], $line['e'], $line['f'], $line['y1']],
                        [$line['g'], $line['h'], $line['i'], $line['z1']],
                        [0, 0, 0, 1],
                    ]);
                    if ($matrix->isSingular()) {
                        yield $this->error(CheckType::Error, error: PartError::RotationMatrixIsSingular, lineNumber: $line['line_number'], text: $line['text']);
                    }
                    break;
                case '2':
                    if ($line['color'] != '24') {
                        yield $this->error(CheckType::Error, error: PartError::InvalidColoredLines, lineNumber: $line['line_number'], text: $line['text']);
                    }
                    $points = MatrixFactory::create([
                        [$line['x1'], $line['y1'], $line['z1']],
                        [$line['x2'], $line['y2'], $line['z2']]
                    ]);
                    if ($points[0] == $points[1]) {
                        yield $this->error(CheckType::Error, error: PartError::IdenticalPoints, lineNumber: $line['line_number']);
                    }
                    break;
                case '3':
                    if ($line['color'] == '24') {
                        yield $this->error(CheckType::Error, error: PartError::InvalidColor24, lineNumber: $line['line_number'], text: $line['text']);
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
                        yield $this->error(CheckType::Error, error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
                        break;
                    }
                    $angle = $this->vector->hasColinearPoints($points);
                    if ($angle !== false) {
                        yield $this->error(CheckType::Error, error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
                    }
                    break;
                case '4':
                    if ($line['color'] == '24') {
                        yield $this->error(CheckType::Error, error: PartError::InvalidColor24, lineNumber: $line['line_number'], text: $line['text']);
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
                        yield $this->error(CheckType::Error, error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
                        break;
                    }
                    $angle = $this->vector->hasColinearPoints($points);
                    if ($angle !== false) {
                        yield $this->error(CheckType::Error, error: PartError::PointsColinear, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
                        break;
                    }

                    if (!$this->vector->isConvexQuad($points)) {
                        yield $this->error(CheckType::Error, error: PartError::QuadNotConvex, lineNumber: $line['line_number'], text: $line['text']);
                        break;
                    }

                    $angle = $this->vector->getMaxCoplanarAngle($points);
                    if ($angle > config('ldraw.check.coplanar_angle_error')) {
                        yield $this->error(CheckType::Error, error: PartError::QuadNotCoplanar, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
                    } elseif ($angle > config('ldraw.check.coplanar_angle_warning')) {
                        yield $this->error(CheckType::Warning, error: PartError::WarningNotCoplanar, lineNumber: $line['line_number'], value: $angle, text: $line['text']);
                    }
                    break;
                case '5':
                    if ($line['color'] != '24') {
                        yield $this->error(CheckType::Error, error: PartError::InvalidColoredLines, lineNumber: $line['line_number'], text: $line['text']);
                    }
                    $points = MatrixFactory::create([
                        [$line['x1'], $line['y1'], $line['z1']],
                        [$line['x2'], $line['y2'], $line['z2']],
                        [$line['x3'], $line['y3'], $line['z3']],
                        [$line['x4'], $line['y4'], $line['z4']]
                    ]);
                    if ($points[0] == $points[1] ||
                        $points[2] == $points[3]) {
                        yield $this->error(CheckType::Error, error: PartError::IdenticalPoints, lineNumber: $line['line_number'], text: $line['text']);
                    }
                    break;
            }
        }  
    }
}
