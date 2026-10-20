<?php

namespace App\Observers;

use App\Models\Part\PartHistory;

class PartHistoryObserver
{
    public function __construct(
    ) {
    }

    public function saved(PartHistory $partHistory): void
    {
    }

    public function deleted(PartHistory $partHistory): void
    {
    }
}
