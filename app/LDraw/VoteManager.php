<?php

namespace App\LDraw;

use App\Events\PartComment;
use App\Events\PartReviewed;
use App\Models\Part;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;

class VoteManager
{
    public function postVote(Part $part, User $user, string $vote_type_code, ?string $comment = null): void
    {
        $vote = $user->votes()->where('part_id', $part->id)->first();

        if (is_null($vote)) {
            $canVote = $user->can('create', [Vote::class, $part, $vote_type_code]);
        } else {
            $canVote = $user->can('update', [$vote, $vote_type_code]);
        }

        if (!$canVote || !$part->isUnofficial()) {
            return;
        }

        $oldVoteIsAdminCert = in_array($vote->vote_type_code ?? null, ['A', 'T']);
        $newVoteIsAdminCert = in_array($vote_type_code, ['A', 'T']);

        switch ($vote_type_code) {
            case 'N':
                $vote->delete();
                PartReviewed::dispatch($part, $user, null, $comment);
                break;
            case 'M':
                PartComment::dispatch($part, $user, $comment);
                break;
            default:
                if (!is_null($vote)) {
                    $vote->vote_type_code = $vote_type_code;
                    $vote->save();
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
        if (($oldVoteIsAdminCert && $vote_type_code === 'N') || $newVoteIsAdminCert) {
            $part
                ->parentsAndSelf
                ->merge($part->descendants)
                ->unofficial()
                ->each(fn (Part $p) => app(PartManager::class)->checkPart($p));
        }
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
        $parts = $part->descendantsAndSelf->unofficial()->where('vote_sort', 2);
        $parts->each(fn (Part $p) => $this->postVote($p, $user, 'A'));
        // Have to recheck parts since sometime, based on processing order, subfiles status is missed
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
            ->each(fn (Part $p) => $this->postVote($p, $user, 'C'));

    }
}
