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
        if (is_null($part->preview())) {
            return;
        }
        if ($part->hasInvalidPreview()) {
            $message(PartError::PreviewInvalid);
            return;
        }
        $preview = $part->preview();
        $codes = Cache::get('ldraw_colour_codes', LdrawColour::pluck('code')->all());
        if (!in_array($preview['color'], $codes)) {
            $message(PartError::PreviewInvalid);
            return;
        }
        $matrix = MatrixFactory::create([
            [$preview['a'], $preview['b'], $preview['c'], $preview['x1']],
            [$preview['d'], $preview['e'], $preview['f'], $preview['y1']],
            [$preview['g'], $preview['h'], $preview['i'], $preview['z1']],
            [0, 0, 0, 1],
        ]);
        if ($matrix->isSingular() || $matrix->isNegativeDefinite()) {
            $message(PartError::PreviewInvalid);
        }
    }
}
