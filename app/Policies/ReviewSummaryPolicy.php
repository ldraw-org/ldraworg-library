<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class ReviewSummaryPolicy
{
    public function manage(User $user): bool
    {
        return $user->can(Permission::ReviewSummaryManage);
    }
}
