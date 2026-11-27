<?php

namespace App\Jobs;

use App\Services\LibraryStatisticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateTrackerHistory implements ShouldQueue
{
    use Queueable;

    public function handle(LibraryStatisticsService $statistics): void
    {
        $statistics->recordTrackerHistory();
    }
}
