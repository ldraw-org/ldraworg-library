<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\GenerateHeader;
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
    public function handle(GenerateHeader $generateHeader): void
    {
        Part::whereIn('id', $this->partIds)
            ->chunkById(100, function ($parts) use ($generateHeader) {
                foreach ($parts as $part) {
                    $generateHeader->updatePartHeader($part);
                    $part->saveQuietly();
                }
            });
    }
}
