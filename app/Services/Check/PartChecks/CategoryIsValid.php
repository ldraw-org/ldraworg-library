<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Models\Part\Part;
use Closure;

class CategoryIsValid implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->type()?->inPartsFolder() && is_null($part->category())) {
            $message(PartError::CategoryInvalid);
        }
    }
}
