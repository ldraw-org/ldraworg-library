<?php

namespace App\Policies;

use App\Enums\VoteType;
use App\Models\User;
use App\Models\Vote;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;

use function GuzzleHttp\default_ca_bundle;

class VotePolicy
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    public function voteAny(User $user, Part $part): bool
    {
        if (!$part->isUnofficial() || $this->settings->tracker_locked) {
            return false;
        }
        if ($part->user_id !== $user->id) {
            return $user->canAny([
                    'part.vote.admincertify',
                    'part.vote.fasttrack',
                    'part.vote.certify',
                    'part.vote.hold',
                    'part.comment',
            ]);
        }

        return $user->canAny([
                'part.vote.admincertify',
                'part.vote.fasttrack',
                'part.vote.own.certify',
                'part.vote.own.hold',
                'part.comment',
            ]);
    }

    public function vote(?User $user, Part $part, VoteType $vote_type): bool
    {
        if (is_null($user) || !$part->isUnofficial() || $this->settings->tracker_locked) {
            return false;
        }

        $vote = $user->votes->firstWhere('part_id', $part->id);

        if (!is_null($vote) && ($vote->user_id !== $user->id || $vote->vote_type === $vote_type)) {
            return false;
        }

        return $this->canCastVoteType($user, $part, $vote_type, $vote);

    }

    protected function canCastVoteType(User $user, Part $part, VoteType $vote_type, ?Vote $vote): bool
    {
        $userIsPartAuthor = (is_null($part->official_part) && $part->user_id === $user->id) ||
            (!is_null($part->official_part) && $part->events->firstWhere('initial_submit', true)?->user_id === $user->id);
        return match($vote_type) {
            VoteType::Comment => $user->can('part.comment'),
            VoteType::CancelVote => !is_null($vote) && $vote->user_id === $user->id,
            VoteType::AdminCertify => $user->can('part.vote.admincertify'),
            VoteType::AdminFastTrack => $user->can('part.vote.fasttrack'),
            VoteType::Certify => $userIsPartAuthor ? $user->can('part.own.vote.certify') : $user->can('part.vote.certify'),
            VoteType::Hold =>  $userIsPartAuthor ? $user->canAny(['part.vote.hold', 'part.own.vote.hold']) : $user->can('part.vote.hold'),
        };
     }

    public function all(User $user): bool
    {
        return !$this->settings->tracker_locked && $user->can('part.vote.certify.all');
    }

    public function allAdmin(User $user): bool
    {
        return !$this->settings->tracker_locked && $user->can('part.vote.admincertify.all');
    }
}
