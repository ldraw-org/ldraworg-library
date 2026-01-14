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
        $partAbove4 = $this->part->type()->isNotPrimitive() && $decimalAbove4;
        if ($decimalAbove5) {
            yield $this->error(CheckType::Error, PartError::DecimalPrecision);
        }
        else if ($partAbove4) {
//            yield $this->error(CheckType::Warning, PartError::WarningDecimalPrecision);
        }
        $trailingZeros = preg_match('~^[1-5]\h+.*?\.\d*?0(\h|$)~um', $body, $matches);
        if ($trailingZeros) {
            yield $this->error(CheckType::Error, PartError::TrailingZeros);
        }
    }
}