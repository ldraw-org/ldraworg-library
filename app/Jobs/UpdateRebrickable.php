<?php

namespace App\Jobs;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateRebrickable implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int  $partId,
        protected bool $updateOfficial = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(PartManager $pm): void
    {
        $pm->updateRebrickable(Part::find($this->partId), $this->updateOfficial);
    }
}
