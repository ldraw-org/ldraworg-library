<?php

namespace App\Services\LDraw\ScheduledTasks;

use App\Models\TrackerHistory;
use App\Models\Part\Part;

class UpdateTrackerHistory
{
    public function __invoke(): void
    {
        TrackerHistory::create([
            'history_data' => Part::select('part_status')->unofficial()->get()->countBy('part_status')->all()
        ]);
    }
}
