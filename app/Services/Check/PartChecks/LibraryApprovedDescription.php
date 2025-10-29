<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class LibraryApprovedDescription implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (is_null($part->description())) {
            return;
        }
        if (preg_match('~[\p{C}\p{Zl}\p{Zp}]~u', $part->description(), $matches)) {
            $message(PartError::InvalidDescription);
        }
    }
}
