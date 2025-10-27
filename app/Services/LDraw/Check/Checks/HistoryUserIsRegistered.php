<?php

namespace App\Services\LDraw\Check\Checks;

use App\Enums\PartError;
use App\Services\LDraw\Check\Contracts\Check;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;
use Closure;

class HistoryUserIsRegistered implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart && !is_null($part->history)) {
            foreach ($part->history as $hist) {
                if (!in_array($hist['type'], ['{', '['])) {
                    continue;
                }
                $user_registered = match ($hist['type']) {
                    '[' => User::where('name', $hist['user'])->exists(),
                    '{' => User::where('realname', $hist['user'])->exists(),
                };
                if (!$user_registered) {
                    $fail(PartError::HistoryAuthorNotRegistered);
                }
            }
        }
    }
}
