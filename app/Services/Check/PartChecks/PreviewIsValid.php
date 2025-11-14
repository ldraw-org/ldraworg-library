<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Services\Check\BaseCheck;
use App\Services\Check\Traits\ParsedPartOnly;

class PreviewIsValid extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if ($this->part->hasInvalidPreview()) {
            yield $this->error(CheckType::Error, PartError::PreviewInvalid);
            return;
        }
    }
}
