<?php

namespace App\LDraw;

use App\Enums\PartStatus;
use App\Enums\VoteType;
use App\Events\PartComment;
use App\Events\PartReviewed;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;

class VoteManager
{
    public function castVote(Part $part, User $user, VoteType $vt, ?string $comment = null): void
    {
        $canVote = $user->can('vote', [Vote::class, $part, $vt]);

        $vote = $user->votes->firstWhere('part_id', $part->id);

        // Can't vote or same vote type
        if (!$canVote || $vote?->vote_type == $vt) {
            return;
        }

        $oldVoteIsAdminCert = $vote?->vote_type == VoteType::AdminCertify || $vote?->vote_type == VoteType::AdminFastTrack;
        $newVoteIsAdminCert = $vt == VoteType::AdminCertify || $vt == VoteType::AdminFastTrack;

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
                ->merge($part->descendants)
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
        $parts = $part->descendantsAndSelf->unique()->unofficial()->where('part_status', PartStatus::AwaitingAdminReview);
        $parts->each(fn (Part $p) => $this->castVote($p, $user, VoteType::AdminCertify));

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
            ->where('part_status', PartStatus::NeedsMoreVotes)
            ->whereDoesntHave('votes', fn (Builder $q) => $q->where('user_id', $user->id)->whereIn('vote_type', [VoteType::AdminCertify, VoteType::AdminFastTrack]))
            ->unofficial()
            ->get()
            ->unique()
            ->each(fn (Part $p) => $this->castVote($p, $user, VoteType::Certify));

    }
}
