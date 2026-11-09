<?php

namespace App\Jobs;

use App\Collections\PartCollection;
use App\Models\Part\Part;
use App\Services\Part\RebrickableSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateRebrickable implements ShouldQueue
{
    use Queueable;


    protected Collection $parts;

    public function __construct(
        Part|Collection $parts,
        protected bool $updateOfficial = false
    ) {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    public function handle(RebrickableSync $partRebrickableService): void
    {
        $this->parts
            ->each(fn (Part $part) => $partRebrickableService->syncRebrickablePart($part, $this->updateOfficial));
    }
}
