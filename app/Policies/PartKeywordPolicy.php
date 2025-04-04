<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Settings\LibrarySettings;

class PartKeywordPolicy
{
    public function __construct(
        protected LibrarySettings $settings
    ) {
    }

    public function manage(User $user)
    {
        return !$this->settings->tracker_locked &&
            $user->can(Permission::PartKeywordsManage);
    }
}
