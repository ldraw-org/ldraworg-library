<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class CategoryIsValid extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type()?->inPartsFolder() && is_null($this->part->category())) {
            yield $this->error(PartError::CategoryInvalid);
        }
    }
}
