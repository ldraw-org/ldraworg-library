<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class BfcIsCcw implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if ($part->bfc() !== 'CCW') {
            $message(PartError::BfcNotCcw);
        }
    }
}
