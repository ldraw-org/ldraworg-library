<?php

namespace App\Services\Check\PartChecks;

use App\Services\Check\BaseCheck;
use App\Models\User;
use App\Services\Check\Enums\PartError;
use App\Services\Check\Traits\ParsedPartOnly;
use Illuminate\Support\Arr;

class HistoryUserIsRegistered extends BaseCheck
{
    use ParsedPartOnly;

    public function check(): iterable
    {
        foreach($this->part->history() as $history) {
            $username = Arr::get($history, 'username');
            $realname = Arr::get($history, 'realname');
            $usernameNotFound = !is_null($username) && User::where('name', $username)->doesntExist();
            $realnameNotFound = !is_null($realname) && User::where('realname', $realname)->doesntExist();

            if ($usernameNotFound || $realnameNotFound) {
                yield $this->error(PartError::HistoryAuthorNotRegistered, value: $username ?? $realname);
                return false;
            }
        }
    }
}
