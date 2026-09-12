<?php

namespace App\Services\Vote;

use App\Enums\PartStatus;
use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;
use App\Services\Part\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoteManager
{
    public function __construct(
        private readonly VoteEligibility $eligibility,
        private readonly VoteRecorder $recorder,
        private readonly PostVoteRechecker $rechecker,
        private readonly Validator $validator,
    ) {
    }

    public function castVote(Part $part, User $user, VoteType $vt, ?string $comment = null): void
    {
        $comment = $this->normalizeComment($comment);
        $existingVote = $this->findExistingVote($part, $user);

        if (! $this->eligibility->canVote($user, $part, $vt, $comment, $existingVote)) {
            return;
        }

        $wasAdminCert = $existingVote?->vote_type?->isAdminCertification() ?? false;
        $isAdminCert = $vt->isAdminCertification();

        DB::transaction(fn () => $this->recorder->record($part, $user, $vt, $comment, $existingVote));

        // Comments don't change vote state, so there's nothing new to (re)check.
        if ($vt !== VoteType::Comment) {
            $part->refresh();
            $part->updatePartStatus();
        }

        if (($wasAdminCert && $vt === VoteType::CancelVote) || $isAdminCert) {
            $this->rechecker->recheck($part);
        }

        $user->notification_parts()->syncWithoutDetaching([$part->id]);
    }

    public function adminCertifyAll(Part $part, User $user): void
    {
        if ($user->cannot('allAdmin', [Vote::class, $part])) {
            return;
        }

        $parts = $part->descendantsAndSelf()
            ->unofficial()
            ->where('part_status', PartStatus::AwaitingAdminReview)
            ->get()
            ->unique();

        $parts->each(fn (Part $p) => $this->castVote($p, $user, VoteType::AdminReview));

        // Belt-and-suspenders: subfile status can depend on processing order,
        // so a final pass catches anything the per-vote recheck missed.
        $parts->each(fn (Part $p) => $this->validator->checkPart($p));
    }

    public function certifyAll(Part $part, User $user): void
    {
        if ($user->cannot('allCertify', [Vote::class, $part])) {
            return;
        }

        $part->descendantsAndSelf()
            ->whereIn('part_status', [PartStatus::Needs2MoreVotes, PartStatus::Needs1MoreVote])
            ->whereDoesntHave('votes', fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->whereIn('vote_type', [VoteType::AdminReview, VoteType::AdminFastTrack]))
            ->unofficial()
            ->get()
            ->unique()
            ->each(fn (Part $p) => $this->castVote($p, $user, VoteType::Certify));
    }

    private function normalizeComment(?string $comment): ?string
    {
        $comment = Str::of($comment)->trim()->toString();

        return $comment === '' ? null : $comment;
    }

    private function findExistingVote(Part $part, User $user): ?Vote
    {
        return $user->votes->firstWhere('part_id', $part->id);
    }
}
