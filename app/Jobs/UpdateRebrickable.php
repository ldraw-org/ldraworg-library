<?php

namespace App\Jobs;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Services\Part\PartRebrickablePartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(PartRebrickablePartService $rbService): void
    {
        $rbSerivec->updateRebrickable($this->part, $this->updateOfficial);
    }
}
