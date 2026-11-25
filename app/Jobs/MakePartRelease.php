<?php

namespace App\Jobs;

use App\Models\Part\PartReleaseRun;
use App\Models\User;
use App\Services\Part\Release\PartReleaseManager;
use App\Services\Support\MemoryLimit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(3600)]
#[Queue('release')]
class MakePartRelease implements ShouldQueue
{
    use Queueable;

    /** Headroom over the worker's 128M default, which a worker near its recycle threshold would mostly have used */
    protected const string MEMORY_LIMIT = '512M';

    public function __construct(
        public User $user,
        public bool $includeLdconfig = false,
        public array $extraFiles = [],
        public ?PartReleaseRun $releaseRun = null,
    ) {
    }

    public function handle(PartReleaseManager $releaseManager, MemoryLimit $memoryLimit): void
    {
        $memoryLimit->raiseTo(self::MEMORY_LIMIT);
        $releaseManager->createRelease($this->user, $this->includeLdconfig, $this->extraFiles, $this->releaseRun);
    }
}
