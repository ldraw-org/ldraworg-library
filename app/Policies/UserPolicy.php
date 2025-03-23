<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    public function add(User $user)
    {
        return $user->can(Permission::UserAdd);
    }

    public function update(User $user, User $model)
    {
        return $user->id !== $model->id &&
            (($user->can(Permission::UserUpdate) && !$model->hasRole('Super Admin')) ||
            $user->can(Permission::UserUpdateSuperuser));
    }

    public function delete(User $user, User $model)
    {
        return $user->can('update', $model) && $user->can(Permission::UserDelete);
    }

}
