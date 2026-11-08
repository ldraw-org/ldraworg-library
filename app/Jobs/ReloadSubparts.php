<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\SyncSubparts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('maintenance')]
class ReloadSubparts implements ShouldQueue
{
    use Queueable;

    protected Collection $parts;

    public function __construct(
        Part|Collection $parts
    ) {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    public function handle(SyncSubparts $syncSubparts): void
    {
        $this->parts
            ->each(fn (Part $part) => $syncSubparts->loadSubparts($part));
    }
}
