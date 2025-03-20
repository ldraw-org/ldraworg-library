<?php

namespace App\LDraw\Check\Checks;

use App\Enums\PartError;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;
use Closure;

class HistoryUserIsRegistered implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart) {
            foreach ($part->history ?? [] as $hist) {
                if (is_null(User::fromAuthor($hist['user'], $hist['user'])->first())) {
                    $fail(PartError::HistoryAuthorNotRegistered);
                }
            }
        }
    }
}
