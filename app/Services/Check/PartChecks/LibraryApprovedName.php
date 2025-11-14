<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class LibraryApprovedName extends BaseCheck
{
    public function check(): iterable
    {
        if (! preg_match('~^[^\p{C}\p{Zl}\p{Zp}]+$~u', $this->part->name(), $matches)) {
            yield $this->error(CheckType::Error, PartError::PartNameInvalid);
        }
    }
}
