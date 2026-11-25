<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class PartReleasePolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::PartReleaseCreate);
    }

    /**
     * Clearing a failed or stalled release's lock-out is for Super Admins only, who pass through Gate::before.
     */
    public function clearLockout(User $user): bool
    {
        return false;
    }
}
