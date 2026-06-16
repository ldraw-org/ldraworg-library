<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Traits\ParsedPartOnly;

class HistoryIsValid extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if ($this->part->hasInvalidHistory()) {
            yield $this->error(PartError::HistoryInvalid);
        }
    }
}
