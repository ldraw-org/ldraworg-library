<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\User\SyncForumUser;
use App\Services\User\SyncUserParts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncUser implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $userId,
        protected array $changes
    )
    {}

    public function handle(SyncUserParts $syncUserParts, SyncForumUser $syncForumUser): void
    {
        $user = User::find($this->userId);
        if ($user !== null) {
            $syncUserParts->handle($user, $this->changes);
            $syncForumUser->handle($user);
        }
    }
}
