<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;
use Closure;

class AuthorInUsers implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart &&
            User::fromAuthor($part->username ?? '', $part->realname ?? '')->doesntExist()
        ) {
            $fail(PartError::AuthorNotRegistered, ['value' => $part->realname ?? $part->username]);
        }
    }
}
