<?php

namespace App\LDraw;

use App\Events\PartComment;
use App\Events\PartReviewed;
use App\Models\Part\Part;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;

class VoteManager
{
    public function castVote(Part $part, User $user, string $vote_type_code, ?string $comment = null): void
    {
        $canVote = $user->can('vote', [Vote::class, $part, $vote_type_code]);

        $vote = $user->votes->firstWhere('part_id', $part->id);

        // Can't vote or same vote type
        if (!$canVote || $vote?->vote_type_code == $vote_type_code) {
            return;
        }

        $oldVoteIsAdminCert = in_array($vote->vote_type_code ?? null, ['A', 'T']);
        $newVoteIsAdminCert = in_array($vote_type_code, ['A', 'T']);

        switch ($vote_type_code) {
            // Cancel vote
            case 'N':
                $vote->delete();
                PartReviewed::dispatch($part, $user, null, $comment);
                break;
            // Comment, doesn't create a vote object
            case 'M':
                PartComment::dispatch($part, $user, $comment);
                break;
            default:
                // Vote changed
                if (!is_null($vote)) {
                    $vote->vote_type_code = $vote_type_code;
                    $vote->save();
                // New vote
                } else {
                    Vote::create([
                        'part_id' => $part->id,
                        'user_id' => $user->id,
                        'vote_type_code' => $vote_type_code,
                    ]);
                }
                PartReviewed::dispatch($part, $user, $vote_type_code, $comment);
        }

        $part->refresh();
        $part->updateVoteSort();

        // Admin vote status changed
        if (($oldVoteIsAdminCert && $vote_type_code === 'N') || $newVoteIsAdminCert) {
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
        if (!$part->isUnofficial() ||
            !$part->type->folder == 'parts/' ||
            $part->descendantsAndSelf->where('vote_sort', '>', 2)->count() > 0 ||
            $user->cannot('create', [Vote::class, $part, 'A']) ||
            $user->cannot('allAdmin', Vote::class)) {
            return;
        }
        $parts = $part->descendantsAndSelf->unique()->unofficial()->where('vote_sort', 2);
        $parts->each(fn (Part $p) => $this->castVote($p, $user, 'A'));

        // Have to recheck parts since sometimes, based on processing order, subfiles status is missed
        $parts->each(fn (Part $p) => app(PartManager::class)->checkPart($p));

    }

    public function certifyAll(Part $part, User $user): void
    {
        if (!$part->isUnofficial() ||
            !$part->type->folder == 'parts/' ||
            $part->descendantsAndSelf->where('vote_sort', '>', 3)->count() > 0 ||
            $user->cannot('create', [Vote::class, $part, 'C']) ||
            $user->cannot('all', Vote::class)) {
            return;
        }
        $part
            ->descendantsAndSelf()
            ->where('vote_sort', 3)
            ->whereDoesntHave('votes', fn (Builder $q) => $q->where('user_id', $user->id)->whereIn('vote_type_code', ['A', 'T']))
            ->unofficial()
            ->get()
            ->unique()
            ->each(fn (Part $p) => $this->castVote($p, $user, 'C'));

    }
}
