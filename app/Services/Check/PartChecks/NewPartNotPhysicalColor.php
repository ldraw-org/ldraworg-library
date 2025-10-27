<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Enums\PartTypeQualifier;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class NewPartNotPhysicalColor implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->type_qualifier() == PartTypeQualifier::PhysicalColour
        ) {
            $message(PartError::NewPartIsPhysicalColor);
        }
    }
}
