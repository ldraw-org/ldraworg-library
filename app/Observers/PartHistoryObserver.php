<?php

namespace App\Observers;

use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Services\Part\GenerateHeader;

class PartHistoryObserver
{
    public function __construct(
    )
    {}

    public function saved(PartHistory $partHistory): void
    {
    }

    public function deleted(PartHistory $partHistory): void
    {
    }
}
