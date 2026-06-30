<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class HasTrailingZeros extends BaseCheck
{
    public function check(): iterable
    {
        $body = $this->part->bodyLines()->pluck('text')->implode("\n");
        $trailingZeros = preg_match('~^[1-5]\h+.*?\.\d*?0(\h|$)~um', $body, $matches);
        if ($trailingZeros) {
            yield $this->error(PartError::TrailingZeros);
        }
    }

}
