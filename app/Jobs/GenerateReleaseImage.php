<?php

namespace App\Jobs;

use App\Models\Part\PartRelease;
use App\Services\Part\Release\ReleaseImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateReleaseImage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected PartRelease $release
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(ReleaseImageGenerator $releaseImageGenerator): void
    {
        $releaseImageGenerator->handle($this->release);
    }
}
