<?php

namespace App\Jobs;

use App\Services\Support\MakeCategoriesTxt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshCategoriesTxt implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(MakeCategoriesTxt $makeCategoriesTxt): void
    {
        $makeCategoriesTxt->handle();
    }
}
