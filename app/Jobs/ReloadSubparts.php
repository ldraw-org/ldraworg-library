<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\SyncSubparts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

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
