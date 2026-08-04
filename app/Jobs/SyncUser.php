<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\User\SyncUserParts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
class SyncUser implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $user,
        protected array $changes
    )
    {}

    public function handle(SyncUserParts $syncUserParts): void
    {
        $syncUserParts->handle($this->user, $this->changes);
    }
}
