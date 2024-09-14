<?php

namespace App\Policies;

use App\Models\ReviewSummary\ReviewSummary;
use App\Models\User;

class ReviewSummaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviewsummary.manage');
    }

    public function manage(User $user, ReviewSummary $summary): bool
    {
        return $user->can('reviewsummary.manage');
    }
}
