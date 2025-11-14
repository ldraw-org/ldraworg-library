<?php

namespace App\Services\Check\PartChecks;

use App\Enums\CheckType;
use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;

class AliasInParts extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type_qualifier() == PartTypeQualifier::Alias &&
            ! $this->part->type()?->inPartsFolder()
        ) {
            yield $this->error(CheckType::Error, PartError::AliasNotInParts);
        }
    }
}
