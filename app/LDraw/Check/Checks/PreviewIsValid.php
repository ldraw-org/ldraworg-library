<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use Closure;
use Illuminate\Support\Arr;
use MathPHP\LinearAlgebra\MatrixFactory;

class PreviewIsValid implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if (is_null($part->preview) || $part->preview == '16 0 0 0 1 0 0 0 1 0 0 0 1') {
            return;
        }
        $result = preg_match_all('/[0-9.-]+/iu', $part->preview, $matrix);
        if ($result != 13) {
            $fail(__('partcheck.preview'));
            return;
        }
        $matrix = Arr::reject($matrix[0], fn (string $value, int $key) => !is_numeric($value));
        if (count($matrix) != 13) {
            $fail(__('partcheck.preview'));
            return;
        }
        $matrix = [
            [$matrix[4], $matrix[5], $matrix[6]],
            [$matrix[7], $matrix[8], $matrix[9]],
            [$matrix[10], $matrix[11], $matrix[12]],
        ];
        $matrix = MatrixFactory::create($matrix);
        if ($matrix->isSingular() || $matrix->isNegativeDefinite()) {
            $fail(__('partcheck.preview'));
        }
    }
}
