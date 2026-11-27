<?php

namespace App\Jobs;

use App\Collections\PartCollection;
use App\Services\Part\Finalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\WithoutRelations;

/**
 * Retries finalizing parts whose finalization failed when they were saved.
 */
#[DeleteWhenMissingModels]
#[Queue('library')]
#[Timeout(1800)]
#[Tries(3)]
#[Backoff(60, 600)]
#[WithoutRelations]
class FinalizeParts implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PartCollection $parts
    ) {
    }

    public function handle(Finalizer $finalizer): void
    {
        $finalizer->handle($this->parts);
    }
}
