<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Models\LdrawColour;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use MathPHP\LinearAlgebra\MatrixFactory;

class PreviewIsValid implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->hasInvalidPreview()) {
            $message(PartError::PreviewInvalid);
            return;
        }
        if (is_null($part->preview())) {
            return;
        }
        $preview = $part->where('meta', 'preview')->where('invalid', false)->first();
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        if (!in_array($preview['color'], $codes)) {
            $message(PartError::PreviewInvalid);
            return;
        }
        $rotation_m = [(float)$preview['a'], (float)$preview['b'], (float)$preview['c'], (float)$preview['x1'],
            (float)$preview['d'], (float)$preview['e'], (float)$preview['f'], (float)$preview['y1'],
            (float)$preview['g'], (float)$preview['h'], (float)$preview['i'], (float)$preview['z1']];
        if (max($rotation_m) > 1) {
            $message(PartError::PreviewInvalid);
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
            $message(PartError::PreviewInvalid);
        }
    }
}
