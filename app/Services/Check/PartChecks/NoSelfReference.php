<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Traits\ParsedPartOnly;

class NoSelfReference extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if (in_array($this->part->name(), $this->part->subparts())
        ) {
            yield $this->error(PartError::CircularReference);
        }
    }
}
