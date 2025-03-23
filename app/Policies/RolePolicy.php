<?php

namespace App\Policies;

use App\Enums\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RoleManage);
    }

    public function manage(User $user, Role $role): bool
    {
        return $role->name == "Super Admin" ? $user->can(Permission::RoleManageSuperuser) : $user->can(Permission::RoleManage);
    }
}
