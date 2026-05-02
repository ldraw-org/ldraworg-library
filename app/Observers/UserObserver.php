<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use App\Services\User\SyncForumUser;
use App\Services\User\SyncUserParts;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(
        protected CacheService  $cacheService,
        protected SyncUserParts $syncUserParts,
        protected SyncForumUser $syncForumUser,
    )
    {}

    public function saved(User $user): void
    {
        if ($user->wasChanged(['name', 'realname', 'license'])) {
            $this->syncUserParts->handle($user, $user->getChanges());
            $this->cacheService->reset(CacheKey::UserOptions);
            $this->cacheService->warm(CacheKey::UserOptions);
        }
        if (app()->environment() == 'production') {
            $this->syncForumUser->handle($user);
        } else {
            Log::debug("User update job run for {$user->name}");
        }

    }

}
