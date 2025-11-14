<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\ParsedPartOnly;

class NoSelfReference extends BaseCheck
{
    use ParsedPartOnly;
    
    public function check(): iterable
    {
        if (in_array($this->part->name(), $this->part->subparts())
        ) {
            yield $this->error(CheckType::Error, PartError::CircularReference);
        }
    }
}
