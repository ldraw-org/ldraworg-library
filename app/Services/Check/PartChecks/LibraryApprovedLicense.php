<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\ParsedPartOnly;

class LibraryApprovedLicense extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if (is_null($this->part->license())) {
            yield $this->error(CheckType::Error, error: PartError::LicenseNotLibraryApproved);
        }
    }
}
