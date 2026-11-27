<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class LibraryApprovedName extends BaseCheck
{
    public const string PATTERN = '~^[\\\\a-z0-9_-]+\.(?:dat|png)$~';

    public function check(): iterable
    {
        if (! preg_match(self::PATTERN, $this->part->name())) {
            yield $this->error(PartError::PartNameInvalid);
        }
    }
}
