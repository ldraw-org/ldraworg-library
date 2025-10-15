<?php

namespace App\Jobs;

use App\Services\LDraw\SupportFiles;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLibraryCsv implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(SupportFiles $support): void
    {
        $support->setLibraryCsv();
    }
}
