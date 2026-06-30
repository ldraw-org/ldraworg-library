<?php

namespace App\Observers;

use App\Jobs\SyncUser;
use App\Models\User;

class UserObserver
{
    public function saved(User $user): void
    {
        SyncUser::dispatch($user->id, $user->getChanges());
    }

}
