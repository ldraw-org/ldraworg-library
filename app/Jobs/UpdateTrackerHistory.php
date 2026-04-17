<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Models\TrackerHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateTrackerHistory implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $stats = Part::query()
            ->unofficial()
            ->selectRaw('part_status, count(*) as count')
            ->groupBy('part_status')
            ->pluck('count', 'part_status')
            ->all();

        TrackerHistory::create([
            'history_data' => $stats,
        ]);
    }
}
