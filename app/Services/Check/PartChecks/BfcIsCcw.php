<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class BfcIsCcw extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->bfc() !== 'CCW') {
            yield $this->error(CheckType::Error, PartError::BfcNotCcw);
        }
    }
}
