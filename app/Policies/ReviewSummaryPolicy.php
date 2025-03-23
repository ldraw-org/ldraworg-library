<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ReviewSummary\ReviewSummary;
use App\Models\User;

class ReviewSummaryPolicy
{
    public function manage(User $user, ReviewSummary $summary): bool
    {
        return $user->can(Permission::ReviewSummaryManage);
    }
}
