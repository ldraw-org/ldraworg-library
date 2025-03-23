<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Part\Part;
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
            $user->can(Permission::PartSubmitRegular) &&
            $user->ca_confirm === true;
    }

    public function update(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $part->isUnofficial() &&
            $user->can(Permission::PartEditHeader) &&
            $user->ca_confirm === true;
    }

    public function move(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $user->can(Permission::PartEditNumber) &&
            $user->ca_confirm === true;
    }

    public function flagManualHold(User $user, Part $part)
    {
        return $part->isUnofficial() &&
            $user->can(Permission::PartFlagManualHold);
    }

    public function flagDelete(User $user, Part $part)
    {
        return $part->isUnofficial() &&
            $user->can(Permission::PartFlagDelete);
    }

    public function flagError(User $user, Part $part)
    {
        return $user->can(Permission::PartFlagError);
    }

    public function delete(User $user, Part $part)
    {
        return !$this->settings->tracker_locked &&
            $part->isUnofficial() &&
            $user->can(Permission::PartDelete);
    }

}
