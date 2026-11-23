<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Omr\OmrModel;
use App\Models\User;

class OmrModelPolicy
{
    public function create(User $user): bool
    {
        return $user->can(Permission::OmrModelApprove);
    }

    public function update(User $user, OmrModel $model): bool
    {
        return $user->can(Permission::OmrModelEdit);
    }
}
