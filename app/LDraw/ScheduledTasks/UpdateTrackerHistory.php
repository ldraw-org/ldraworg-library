<?php

namespace App\LDraw\ScheduledTasks;

use App\Models\TrackerHistory;
use App\Models\Part\Part;

class UpdateTrackerHistory
{
    public function __invoke(): void
    {
        TrackerHistory::create([
            'history_data' => Part::unofficial()->pluck('part_status')->countBy('part_status')->all()
        ]);
    }
}
