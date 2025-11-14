<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\ParsedPartOnly;

class HistoryIsValid extends BaseCheck
{
    use ParsedPartOnly;
  
    public function check(): iterable
    {
        if ($this->part->hasInvalidHistory()) {
            yield $this->error(CheckType::Error, PartError::HistoryInvalid);
        }
    }
}
