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
        public array $partIds
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Part::whereIn('id', $this->partIds)
            ->chunkById(100, function ($parts) {
                foreach ($parts as $part) {
                    $part->generateHeader();
                }
            });
    }
}
