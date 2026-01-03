<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class DecimalPrecisionWarning extends BaseCheck
{
    public function check(): iterable
    {
        $body = $this->part->bodyLines()->pluck('text')->implode("\n");
        $decimalAbove4 = preg_match('~^[1-5]\h+.*?\d*\.\d{5,}~um', $body, $matches);
        $decimalAbove5 = preg_match('~^[1-5]\h+.*?\d*\.\d{6,}~um', $body, $matches);
        $primitiveAbove5 = $this->part->type()->isPrimitive() && $decimalAbove5;
        $partAbove4 = $this->part->type()->isNotPrimitive() && $decimalAbove4;
        if ($primitiveAbove5 || $partAbove4) {
            yield $this->error(CheckType::Warning, PartError::WarningDecimalPrecision);
        }
    }
}