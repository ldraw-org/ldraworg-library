<?php

namespace App\Observers;

use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Services\Part\GenerateHeader;

class PartHistoryObserver
{
    public function __construct(
        protected GenerateHeader $generateHeader
    )
    {}

    public function saved(PartHistory $partHistory): void
    {
        if ($partHistory->part_id !== null &&
            $partHistory->wasRecentlyCreated &&
            $partHistory->wasChanged(['created_at', 'user_id', 'comment'])) {
            $this->generateHeader($partHistory->part);
        }
    }

    public function deleted(PartHistory $partHistory): void
    {
        if ($partHistory->part_id !== null) {
            $this->generateHeader($partHistory->part);
        }
    }

    protected function generateHeader(Part $part): void
    {
        $this->generateHeader->updatePartHeader($part);
        $part->saveQuietly();
    }
}
