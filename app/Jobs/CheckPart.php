<?php

namespace App\Jobs;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Services\Part\PartCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPart implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Part $part
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(PartCheckService $checker): void
    {
        $checker->checkPart($this->part);
    }
}
