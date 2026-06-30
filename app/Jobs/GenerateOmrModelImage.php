<?php

namespace App\Jobs;

use App\Models\Omr\OmrModel;
use App\Services\OmrModel\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOmrModelImage implements ShouldQueue
{
    use Queueable;
    public function __construct(
        protected int $omrModelId
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $omrModel = OmrModel::find($this->omrModelId);

        if (!$omrModel) {
            return;
        }

        $imageGenerator->regenerateImage($omrModel);
    }
}
