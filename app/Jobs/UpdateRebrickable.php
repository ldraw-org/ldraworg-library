<?php

namespace App\Jobs;

use App\LDraw\PartManager;
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
        protected Part $part
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(PartManager $pm): void
    {
        $pm->updateRebrickable($this->part);
    }
}
