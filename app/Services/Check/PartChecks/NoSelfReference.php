<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class NoSelfReference implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (in_array($part->name(), $part->subparts())
        ) {
            $message(PartError::CircularReference);
        }
    }
}
