<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class BfcIsCcw extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->bfc() !== 'CCW') {
            yield $this->error(PartError::BfcNotCcw);
        }
    }
}
