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
}
