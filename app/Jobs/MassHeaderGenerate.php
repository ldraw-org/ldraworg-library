<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\GenerateHeader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('maintenance')]
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
