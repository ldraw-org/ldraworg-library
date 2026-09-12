<?php

namespace App\Services\Vote;

use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;

class VoteEligibility
{
    public function canVote(User $user, Part $part, VoteType $vt, ?string $comment, ?Vote $existingVote): bool
    {
        if (! $user->can('vote', [Vote::class, $part, $vt])) {
            return false;
        }

        return match ($vt) {
            VoteType::Comment => ! is_null($comment),
            VoteType::CancelVote => ! is_null($existingVote),
            default => $existingVote?->vote_type !== $vt,
        };
    }
}
