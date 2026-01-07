<?php

namespace App\Listeners;

use App\Events\PartMissingSubpartsResolved;
use App\Jobs\CheckPart;
use App\Jobs\UpdateImage;
use App\Jobs\UpdateRebrickable;
use App\Services\Part\PartAdminReadinessService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandlePartMissingSubpartsResolved implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected PartAdminReadinessService $adminReadiness,
    ) {}
    /**
     * Handle the event.
     */
    public function handle(PartMissingSubpartsResolved $event): void
    {
        UpdateImage::dispatch($event->part);
        CheckPart::dispatch($event->part);
        UpdateRebrickable::dispatch($event->part);
        $this->adminReadiness->sync($event->part);
    }
}
