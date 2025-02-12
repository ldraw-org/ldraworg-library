<?php

namespace App\Jobs;

use App\LDraw\OmrModelManager;
use App\LDraw\PartManager;
use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateImage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Part|OmrModel $m
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->m instanceof Part) {
            app(PartManager::class)->updateImage($this->m);
        } elseif ($this->m instanceof OmrModel) {
            app(OmrModelManager::class)->updateImage($this->m);
        }

    }
}
