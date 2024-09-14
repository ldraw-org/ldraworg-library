<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;
use App\Settings\LibrarySettings;

class PartPolicy
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    public function create(User $user)
    {
        return !$this->settings->tracker_locked &&
            $user->can('part.submit.regular');
    }

    public function update(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $part->isUnofficial() &&
            $user->can('part.edit.header') && $user->ca_confirm === true;
    }

    public function move(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $user->can('part.edit.number') && $user->ca_confirm === true;
    }

    public function flagManualHold(User $user, Part $part)
    {
        return $part->isUnofficial() && $user->can('part.flag.manual-hold');
    }

    public function flagDelete(User $user, Part $part)
    {
        return $part->isUnofficial() && $user->can('part.flag.delete');
    }

    public function delete(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $part->isUnofficial() && $user->can('part.delete');
    }

}
