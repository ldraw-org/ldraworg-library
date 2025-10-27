<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Models\Part\Part;
use Closure;

class HistoryIsValid implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->hasInvalidHistory()) {
            $message(PartError::HistoryInvalid);
        }
    }
}
