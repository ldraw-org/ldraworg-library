<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\LDraw\Managers\Part\PartReleaseManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(3600)]
class MakePartRelease implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public bool $includeLdconfig = false,
        public array $extraFiles = []
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $updater = new PartReleaseManager($this->user, $this->includeLdconfig, $this->extraFiles);
        $updater->createRelease();
    }
}
