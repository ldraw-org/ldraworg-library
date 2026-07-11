<?php

namespace App\Jobs;

use App\Services\Support\MakePtReleases;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshPtReleases implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(MakePtReleases $makePtReleases): void
    {
        $makePtReleases->handle();
    }
}
