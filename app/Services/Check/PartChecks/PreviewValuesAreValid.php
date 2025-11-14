<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Models\LdrawColour;
use App\Services\Check\BaseCheck;
use Illuminate\Support\Facades\Cache;
use MathPHP\LinearAlgebra\MatrixFactory;

class PreviewValuesAreValid extends BaseCheck
{
    public function check(): iterable
    {
        if (is_null($this->part->preview())) {
            return;
        }
        $preview = $this->part->preview();
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        if (!in_array($preview['color'], $codes)) {
            yield $this->error(CheckType::Error, PartError::PreviewInvalid);
            return;
        }
        $rotation_m = [
            (float)$preview['a'], (float)$preview['b'], (float)$preview['c'], (float)$preview['x1'],
            (float)$preview['d'], (float)$preview['e'], (float)$preview['f'], (float)$preview['y1'],
            (float)$preview['g'], (float)$preview['h'], (float)$preview['i'], (float)$preview['z1']
        ];
        if (max($rotation_m) > 1) {
            yield $this->error(CheckType::Error, PartError::PreviewInvalid);
            return;
        }
        $matrix = MatrixFactory::create([
            [(float)$preview['a'], (float)$preview['b'], (float)$preview['c']],
            [(float)$preview['d'], (float)$preview['e'], (float)$preview['f']],
            [(float)$preview['g'], (float)$preview['h'], (float)$preview['i']]
        ]);
        $matrix->setError(1e-4);
        $det = round($matrix->det(), 4);
        if ($det != 1 || ($det == 1 && !$matrix->isOrthogonal())) {
            yield $this->error(CheckType::Error, PartError::PreviewInvalid);
        }
    }
}
