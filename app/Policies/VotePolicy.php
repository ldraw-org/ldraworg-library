<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vote;
use App\Models\Part;
use Illuminate\Auth\Access\HandlesAuthorization;

class VotePolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, Part $part)
    {
        if ($part->user_id == $user->id) {
            return $user->hasAnyPermission([
                'part.vote.certify', 
                'part.vote.admincertify',
                'part.vote.fasttrack',
                'part.vote.hold',
                'part.comment',
                'part.own.vote.certify', 
                'part.own.vote.hold',
                'part.own.comment',
            ]);
        }
        return $user->hasAnyPermission([
            'part.vote.certify', 
            'part.vote.admincertify',
            'part.vote.fasttrack',
            'part.vote.hold',
            'part.vote.novote',
            'part.comment',
        ]); 
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vote  $vote
     * @return mixed
     */
    public function update(User $user, Vote $vote)
    {
        return $vote->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vote  $vote
     * @return mixed
     */
    public function delete(User $user, Vote $vote)
    {
        return $vote->user_id === $user->id;
    }
}
