<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class SyncForumUser
{
    public function handle(User $user)
    {
        if ($user->forum_user !== null && app()->environment() == 'production') {
            $user->forum_user->username = $user->realname;
            $user->forum_user->email = $user->email;
            $user->forum_user->loginname = $user->name;
            foreach (config('ldraw.mybb-groups') as $role => $group) {
                if ($user->hasRole($role)) {
                    $user->forum_user->addGroup($group);
                }
            }
            $user->forum_user->save();
        } else {
            Log::debug("User update job run for {$user->name}");
        }
    }
}
