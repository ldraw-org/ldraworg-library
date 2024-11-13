<?php

namespace App\Policies;

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

    public function vote(User $user, Part $part): bool
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

    public function create(User $user, Part $part, string $vote_type): bool
    {
        if (!$part->isUnofficial() || $this->settings->tracker_locked) {
            return false;
        }
        switch ($vote_type) {
            case 'M':
                return $user->can('part.comment');
                break;
            case 'A':
                return $user->can('part.vote.admincertify');
                break;
            case 'T':
                return $user->can('part.vote.fasttrack');
                break;
            case 'C':
                if ($part->user_id === $user->id) {
                    return $user->can('part.own.vote.certify');
                } else {
                    return $user->can('part.vote.certify');
                }
                break;
            case 'H':
                if ($part->user_id !== $user->id) {
                    return $user->can('part.vote.hold');
                } else {
                    return $user->canAny(['part.vote.hold', 'part.own.vote.hold']);
                }
                break;
        }
        return false;
    }

    public function update(User $user, Vote $vote, string $vote_type): bool
    {
        if (!$vote->part->isUnofficial() || $vote->user_id !== $user->id || $this->settings->tracker_locked) {
            return false;
        }
        switch ($vote_type) {
            case 'M':
                return $user->can('part.comment');
                break;
            case 'N':
                return true;
                break;
            case 'A':
                return $user->can('part.vote.admincertify');
                break;
            case 'T':
                return $user->can('part.vote.fasttrack');
                break;
            case 'C':
                if ($vote->part->user_id === $user->id) {
                    return $user->can('part.own.vote.certify');
                } else {
                    return $user->can('part.vote.certify');
                }
                break;
            case 'H':
                if ($vote->part->user_id !== $user->id) {
                    return $user->can('part.vote.hold');
                } else {
                    return $user->canAny(['part.vote.hold', 'part.own.vote.hold']);
                }
                break;
        }
        return false;
    }

    public function delete(User $user, Vote $vote): bool
    {
        return !$this->settings->tracker_locked && $vote->user_id === $user->id ;
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
