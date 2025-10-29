<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Support\Arr;

class AuthorInUsers implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        if (is_null($part->authorUser())) {
            $authorLine = $part->where('meta', 'author')->first();
            $text = Arr::get($authorLine, 'text');
            $message(error: PartError::AuthorNotRegistered, value: $text);
        }
    }
}
