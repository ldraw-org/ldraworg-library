<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\SyncFix;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class SyncFixes implements ShouldQueue
{
    use Queueable;

    protected Collection $parts;

    public function __construct(Part|Collection $parts)
    {
        $this->parts = $parts instanceof Part
            ? new Collection([$parts])
            : $parts;
    }

    public function handle(SyncFix $syncFix): void
    {
        $this->parts->load(['official_part', 'unofficial_part']);
        $this->parts->each(fn (Part $part) => $syncFix->handle($part));
    }
}
