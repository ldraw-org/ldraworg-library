<?php

namespace App\Jobs;

use App\Models\Part\Part;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class MassHeaderGenerate implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $parts
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->parts->each(fn (Part $p) => $p->generateHeader());
    }
}
