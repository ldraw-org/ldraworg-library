<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Traits\ParsedPartOnly;

class LibraryApprovedLicense extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if (is_null($this->part->license())) {
            yield $this->error(PartError::LicenseNotLibraryApproved);
        }
    }
}
