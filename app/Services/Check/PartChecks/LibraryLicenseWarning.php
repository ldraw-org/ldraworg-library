<?php

namespace App\Services\Check\PartChecks;

use App\Enums\License;
use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartWarning;
use App\Services\Check\Traits\PartOnly;

class LibraryLicenseWarning extends BaseCheck
{
    use PartOnly;

    public function check(): iterable
    {
        if ($this->part->rawPart()->isUnofficial() && $this->part->license() != License::CC_BY_4) {
            yield $this->error(PartWarning::WarningLicense);
        }
    }
}
