<?php

namespace App\Policies;

use App\Models\Poll\Poll;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PollPolicy
{
    public function vote(User $user, Poll $poll): bool
    {
        return $poll->ends_on >= now()
            && $poll->enabled
            && $user->can('member.poll.vote')
            && $poll->items()->whereHas(
                'votes',
                fn (Builder $query) => $query->where('user_id', $user->id)
            )->count() == 0;
    }
}
