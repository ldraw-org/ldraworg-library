<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Part\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GeneratePartImage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $partId
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $part = Part::find($this->partId);

        if (!$part) {
            return;
        }

        $imageGenerator->regenerateImage($part);
    }
}
