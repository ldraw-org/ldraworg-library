<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class CategoryIsValid extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type()?->inPartsFolder() && is_null($this->part->category())) {
            yield $this->error(CheckType::Error, PartError::CategoryInvalid);
        }
    }
}
