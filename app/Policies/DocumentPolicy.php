<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class DocumentPolicy
{
    public function manage(User $user) {
        return $user->can(Permission::DocumentManage);
    }
}
