<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Part\Part;
use App\Services\LDraw\ZipFiles;

class UpdateZip implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected Part $part,
        protected ?string $oldfilename = null
    ) {
    }

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
