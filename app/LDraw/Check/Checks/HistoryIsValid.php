<?php

namespace App\LDraw\Check\Checks;

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;
use Closure;

class HistoryIsValid implements Check
{
    public function check(ParsedPart|Part $part, Closure $fail): void
    {
        if ($part instanceof ParsedPart) {
            $hcount = count($part->history ?? []);
            if ($hcount != mb_substr_count($part->rawText, '!HISTORY')) {
                $fail(__('partcheck.history.invalid'));
            }
            foreach ($part->history ?? [] as $hist) {
                if (is_null(User::fromAuthor($hist['user'])->first())) {
                    $fail(__('partcheck.history.author'));
                }
            }
        }
    }
}
