<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class DocumentCategoryPolicy
{
    public function manage(User $user) {
        return $user->can(Permission::DocumentCategoryManage);
    }
}
