<?php

namespace App\Policies;

use App\Enums\PartStatus;
use App\Enums\Permission;
use App\Enums\VoteType;
use App\Models\User;
use App\Models\Vote;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;

class VotePolicy
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    public function voteAny(User $user, Part $part): bool
    {
        if ($part->isOfficial() || $this->settings->tracker_locked) {
            return false;
        }
        if ($part->user_id !== $user->id) {
            return $user->canAny([
                Permission::PartVoteAdminCertify,
                Permission::PartVoteFastTrack,
                Permission::PartVoteCertify,
                Permission::PartVoteHold,
                Permission::PartComment,
            ]);
        }

        return $user->canAny([
            Permission::PartVoteAdminCertify,
            Permission::PartVoteFastTrack,
            Permission::PartOwnVoteCertify,
            Permission::PartOwnVoteHold,
            Permission::PartComment,
        ]);
    }

    public function vote(?User $user, Part $part, VoteType $vote_type): bool
    {
        if (is_null($user) || $part->isOfficial() || $this->settings->tracker_locked) {
            return false;
        }
        $vote = $user->votes()->firstWhere('part_id', $part->id);

        if (!is_null($vote) && $vote->vote_type === $vote_type) {
            return false;
        }

        return $this->canCastVoteType($user, $part, $vote_type, $vote);
    }

    protected function canCastVoteType(User $user, Part $part, VoteType $vote_type, ?Vote $vote): bool
    {
        $userIsPartAuthor = (is_null($part->official_part) && $part->user_id === $user->id) ||
            (!is_null($part->official_part) && $part->events->firstWhere('initial_submit', true)?->user_id === $user->id);
        return match($vote_type) {
            VoteType::Comment => $user->can(Permission::PartComment),
            VoteType::CancelVote => !is_null($vote) && $vote->user_id === $user->id,
            VoteType::AdminReview => $user->can(Permission::PartVoteAdminCertify),
            VoteType::AdminFastTrack => $user->can(Permission::PartVoteFastTrack),
            VoteType::Certify => $userIsPartAuthor ? $user->can(Permission::PartOwnVoteCertify) : $user->can(Permission::PartVoteCertify),
            VoteType::Hold => $userIsPartAuthor ? $user->canAny([Permission::PartVoteHold, Permission::PartOwnVoteHold]) : $user->can(Permission::PartVoteHold),
        };
    }

    public function allCertify(User $user, Part $part): bool
    {
        if (
            $this->settings->tracker_locked ||
            !$part->isUnofficial() ||
            !$part->type->inPartsFolder() ||
            !$user->can(Permission::PartVoteCertifyAll)
        ) {
            return false;
        }

        $unofficial = $part->descendantsAndSelf->unofficial();

        if ($unofficial->where('part_status', PartStatus::ErrorsFound)->isNotEmpty()) {
            return false;
        }

        return $unofficial
            ->whereIn('part_status', [PartStatus::Needs2MoreVotes, PartStatus::Needs1MoreVote])
            ->contains(fn (Part $p): bool => $this->vote($user, $p, VoteType::Certify));
    }

    public function allAdmin(User $user, Part $part): bool
    {
        if (
            $this->settings->tracker_locked ||
            !$part->isUnofficial() ||
            !$part->type->inPartsFolder() ||
            !$user->can(Permission::PartVoteAdminCertifyAll)
        ) {
            return false;
        }

        $unofficial = $part->descendantsAndSelf->unofficial();

        if ($unofficial->where('ready_for_admin', false)->isNotEmpty()) {
            return false;
        }

        return $unofficial
            ->where('part_status', PartStatus::AwaitingAdminReview)
            ->contains(fn (Part $p): bool => $this->vote($user, $p, VoteType::AdminReview));
    }
}
