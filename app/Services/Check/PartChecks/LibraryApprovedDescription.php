<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class LibraryApprovedDescription extends BaseCheck
{
    public function check(): iterable
    {
        if (preg_match('~[\p{C}\p{Zl}\p{Zp}]~u', $this->part->description(), $matches)) {
            yield $this->error(PartError::InvalidDescription);
        }
    }
}
