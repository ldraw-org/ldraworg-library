<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Part\Release\PartReleaseManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(3600)]
#[Queue('release')]
class MakePartRelease implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public bool $includeLdconfig = false,
        public array $extraFiles = []
    ) {
    }

    public function handle(PartReleaseManager $releaseManager): void
    {
        $releaseManager->createRelease($this->user, $this->includeLdconfig, $this->extraFiles);
    }
}
