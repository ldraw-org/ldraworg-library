<?php

namespace App\Services\Vote;

use App\Enums\VoteType;
use App\Events\PartComment;
use App\Events\PartReviewed;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;

class VoteRecorder
{
    public function record(Part $part, User $user, VoteType $vt, ?string $comment, ?Vote $existingVote): void
    {
        if ($vt === VoteType::CancelVote) {
            $existingVote->delete();
            PartReviewed::dispatch($part, $user, null, $comment);

            return;
        }

        if ($vt === VoteType::Comment) {
            PartComment::dispatch($part, $user, $comment);

            return;
        }

        if ($existingVote) {
            $existingVote->vote_type = $vt;
            $existingVote->save();
        } else {
            Vote::create([
                'part_id' => $part->id,
                'user_id' => $user->id,
                'vote_type' => $vt,
            ]);
        }

        PartReviewed::dispatch($part, $user, $vt, $comment);
    }
}
