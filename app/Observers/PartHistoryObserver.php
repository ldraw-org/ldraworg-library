<?php

namespace App\Observers;

use App\Models\Part\PartHistory;

class PartHistoryObserver
{
    public function saved(PartHistory $history): void
    {
        if ($history->wasChanged(['created_at', 'user_id', 'comment'])) {
            $history->part->generateHeader(true);
        }
    }
}
