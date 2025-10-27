<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class LibraryApprovedName implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (! preg_match(config('ldraw.patterns.library_approved_name'), $part->name(), $matches)) {
            $message(PartError::PartNameInvalid);
        }
    }
}
