<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartCategory;
use App\Enums\PartTypeQualifier;
use App\Services\Check\BaseCheck;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Traits\ParsedPartOnly;

class NewPartNotPhysicalColor extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        if ($this->part->type_qualifier() == PartTypeQualifier::PhysicalColour &&
            $this->part->category() !== PartCategory::Obsolete
        ) {
            yield $this->error(PartError::NewPartIsPhysicalColor);
        }
    }
}
