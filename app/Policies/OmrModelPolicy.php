<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class OmrModelPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::OmrModelSubmit);
    }
}
