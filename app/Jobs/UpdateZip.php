<?php

namespace App\Jobs;

use App\Models\Part\Part;
use App\Services\Support\ZipFiles;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;

#[Timeout(1800)]
#[Queue('maintenance')]
class UpdateZip implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected Part $part,
        protected ?string $oldfilename = null
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZipFiles $zipfiles)
    {
        $zipfiles->unofficialZip($this->part, $this->oldfilename);
    }
}
