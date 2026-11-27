<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class LibraryApprovedName extends BaseCheck
{
    public function check(): iterable
    {
        if (! preg_match('~^[\\\\a-z0-9_-]+\.(?:dat|png)$~', $this->part->name(), $matches)) {
            yield $this->error(PartError::PartNameInvalid);
        }
    }
}
