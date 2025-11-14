<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;

class LibraryApprovedDescription extends BaseCheck
{
    public function check(): iterable
    {
        if (preg_match('~[\p{C}\p{Zl}\p{Zp}]~u', $this->part->description(), $matches)) {
            yield $this->error(CheckType::Error, PartError::InvalidDescription);
        }
    }
}
