<?php

namespace App\Services\Check\PartChecks;

use App\Enums\PartError;
use App\Services\Check\Contracts\Check;
use App\Services\Parser\ParsedPartCollection;
use App\Models\User;
use Closure;
use Illuminate\Support\Arr;

class HistoryUserIsRegistered implements Check
{
    public function check(ParsedPartCollection $part, Closure $message): void
    {
        collect($part->history())
            ->each(function (array $history) use ($message) {
                $username = Arr::get($history, 'username');
                $realname = Arr::get($history, 'realname');
                if (!is_null($username) && !is_null($realname)) {
                    $message(PartError::HistoryAuthorNotRegistered);
                } elseif (!is_null($username) && User::where('name', $username)->doesntExist()) {
                    $message(PartError::HistoryAuthorNotRegistered);
                } elseif (!is_null($realname) && User::where('realname', $realname)->doesntExist()) {
                    $message(PartError::HistoryAuthorNotRegistered);
                }
            });
    }
}
