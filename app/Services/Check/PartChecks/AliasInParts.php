<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;

class AliasInParts extends BaseCheck
{
    public function check(): iterable
    {
        if ($this->part->type_qualifier() == PartTypeQualifier::Alias &&
            ! $this->part->type()?->inPartsFolder()
        ) {
            yield $this->error(PartError::AliasNotInParts);
        }
    }
}
