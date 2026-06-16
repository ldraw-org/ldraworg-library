<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class DecimalPrecisionExceeded extends BaseCheck
{
    public function check(): iterable
    {
        $body = $this->part->bodyLines()->pluck('text')->implode("\n");
        $decimalAbove5 = preg_match('~^[1-5]\h+.*?\d*\.\d{6,}~um', $body, $matches);
        if ($decimalAbove5) {
            yield $this->error(PartError::DecimalPrecision);
        }
    }
}
