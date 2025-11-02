<?php

namespace App\Services\Check\PartChecks;

use App\Enums\License;
use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class LibraryLicenseWarning implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->license() != License::CC_BY_4) {
            $message(error: PartError::WarningLicense);
        }
    }
}
