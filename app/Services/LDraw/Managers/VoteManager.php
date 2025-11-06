<?php

namespace App\Services\LDraw\Managers;

use App\Enums\PartStatus;
use App\Enums\VoteType;
use App\Events\PartComment;
use App\Events\PartReviewed;
use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class VoteManager
{
    public function castVote(Part $part, User $user, VoteType $vt, ?string $comment = null): void
    {
        $canVote = $user->can('vote', [Vote::class, $part, $vt]);

        $vote = $user->votes->firstWhere('part_id', $part->id);

        $comment = Str::of($comment)->trim()->toString();
        $comment = $comment == '' ? null : $comment;
        // Can't vote or same vote type
        if (!$canVote || $vote?->vote_type == $vt || ($vt == VoteType::Comment && is_null($comment))) {
            return;
        }

        $oldVoteIsAdminCert = $vote?->vote_type == VoteType::AdminReview || $vote?->vote_type == VoteType::AdminFastTrack;
        $newVoteIsAdminCert = $vt == VoteType::AdminReview || $vt == VoteType::AdminFastTrack;

        switch ($vt) {
            // Cancel vote
            case VoteType::CancelVote:
                $vote->delete();
                PartReviewed::dispatch($part, $user, null, $comment);
                break;
                // Comment, doesn't create a vote object
            case VoteType::Comment:
                PartComment::dispatch($part, $user, $comment);
                break;
            default:
                // Vote changed
                if (!is_null($vote)) {
                    $vote->vote_type = $vt;
                    $vote->save();
                    // New vote
                } else {
                    Vote::create([
                        'part_id' => $part->id,
                        'user_id' => $user->id,
                        'vote_type' => $vt,
                    ]);
                }
                PartReviewed::dispatch($part, $user, $vt, $comment);
        }

        $part->refresh();
        $part->updatePartStatus();

        // Admin vote status changed
        if (($oldVoteIsAdminCert && $vt === VoteType::CancelVote) || $newVoteIsAdminCert) {
            $part
                ->parentsAndSelf
                ->merge($part->descendants->unique())
                ->unofficial()
                ->each(fn (Part $p) => app(PartManager::class)->checkPart($p));
        }

        // Add user to notifications list
        $user->notification_parts()->syncWithoutDetaching([$part->id]);
    }

    public function adminCertifyAll(Part $part, User $user): void
    {
        if ($user->cannot('allAdmin', [Vote::class, $part])) {
            return;
        }
        $parts = $part->descendantsAndSelf()->unofficial()->where('part_status', PartStatus::AwaitingAdminReview)->get()->unique();
        $parts->each(fn (Part $p) => $this->castVote($p, $user, VoteType::AdminReview));

        // Have to recheck parts since sometimes, based on processing order, subfiles status is missed
        $parts->each(fn (Part $p) => app(PartManager::class)->checkPart($p));

    }

    public function certifyAll(Part $part, User $user): void
    {
        if ($user->cannot('allCertify', [Vote::class, $part])) {
            return;
        }
        $part
            ->descendantsAndSelf()
            ->whereIn('part_status', [PartStatus::Needs2MoreVotes, PartStatus::Needs1MoreVote])
            ->whereDoesntHave('votes', fn (Builder $q) => $q->where('user_id', $user->id)->whereIn('vote_type', [VoteType::AdminReview, VoteType::AdminFastTrack]))
            ->unofficial()
            ->get()
            ->unique()
            ->each(fn (Part $p) => $this->castVote($p, $user, VoteType::Certify));

    }
}
