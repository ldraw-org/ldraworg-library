<?php

namespace App\Jobs;

use App\Services\Support\DeleteOrphanMedia as DeleteOrphanMediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class DeleteOrphanMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(DeleteOrphanMediaService $deleteOrphanMedia): void
    {
        $deleteOrphanMedia->handle();
    }
}
