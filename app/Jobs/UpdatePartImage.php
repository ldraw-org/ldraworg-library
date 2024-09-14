<?php

namespace App\Jobs;

use App\LDraw\PartManager;
use App\Models\Part;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePartImage implements ShouldQueue
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
    public function handle(): void
    {
        app(PartManager::class)->updatePartImage($this->part);
    }
}
