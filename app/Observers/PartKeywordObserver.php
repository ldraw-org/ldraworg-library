<?php

namespace App\Observers;

use App\Jobs\MassHeaderGenerate;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;

class PartKeywordObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(PartKeyword $keyword): void
    {
        if ($keyword->wasChanged('keyword')) {
            $this->dispatchMassUpdate($keyword);
        }
    }


    public function deleting(PartKeyword $keyword): void
    {
        $this->dispatchMassUpdate($keyword);
    }

    protected function dispatchMassUpdate(PartKeyword $keyword): void
    {
        // Use the query builder (parts()) to get IDs efficiently
        $partIds = $keyword->parts()->pluck('id')->all();

        if (!empty($partIds)) {
            MassHeaderGenerate::dispatch($partIds);
        }
    }
}
