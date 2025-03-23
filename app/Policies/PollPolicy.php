<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Poll\Poll;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class PollPolicy
{
    public function vote(User $user, Poll $poll): Response
    {
        if ($user->cannot(Permission::PollVote)) {
            return Response::deny('You do not have permission to vote in polls.');
        }

        if (!$poll->enabled) {
            return Response::deny('This poll is not enabled.');
        }

        if ($poll->ends_on < now()) {
            return Response::deny('This poll has ended.');
        }

        if (!$poll->items()->whereHas(
            'votes',
            fn (Builder $query) => $query->where('user_id', $user->id)
        )->exists()) {
            return Response::deny('You have already voted in this poll.');
        }

        return Response::allow();
    }

    public function voteAny(User $user): Response
    {
        return $user->can(Permission::PollVote) ? Response::allow() : Response::deny('You do not have permission to vote in polls.');
    }

    public function manage(User $user, Poll $poll): bool
    {
        return $user->can(Permission::PollManage);
    }
}
