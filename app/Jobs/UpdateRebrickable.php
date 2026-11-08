<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\RebrickableSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateRebrickable implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Part $part,
        protected bool $updateOfficial = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(RebrickableSync $partRebrickableService): void
    {
        $partRebrickableService->syncRebrickablePart($this->part, $this->updateOfficial);
    }
}
