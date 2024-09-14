<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    public function viewAny(?User $user)
    {
        return true;
    }

    public function view(?User $user, User $model)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->canAny([
            'user.add',
            'user.add.nonadmin',
        ]);
    }

    public function update(User $user, User $model)
    {
        return Auth::user()?->id !== $user->id
            ? !in_array($user->id, config('auth.superusers')) && $user->can('user.modify.superuser')
            : $user->can('user.modify');
        ;
    }
}
