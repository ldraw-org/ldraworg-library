<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;

class AuthorInUsers implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (is_null($part->author())) {
            [$username, $realname] = $part->authorRaw();
            $message(error: PartError::AuthorNotRegistered, value: $realname ?? $username);
        }
    }
}
