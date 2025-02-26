<?php

namespace App\LDraw\ScheduledTasks;

use App\Enums\PartStatus;
use App\Models\TrackerHistory;
use App\Models\Part\Part;

class UpdateTrackerHistory
{
    public function __invoke(): void
    {
        TrackerHistory::create([
            'history_data' => Part::unofficial()->pluck('part_status')->countBy(fn (PartStatus $p) => $p->value)->all()
        ]);
    }
}
