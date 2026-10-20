<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartWarning;

class RetiredCategoryWarning extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type()?->inPartsFolder() && $this->part->category()?->isRetired() === true) {
            yield $this->error(PartWarning::WarningRetiredCategory, value: $this->part->category()->retiredMessage(), type: $this->part->category()->value, );
        }
    }
}
