<?php

namespace App\Jobs;

use App\Models\Omr\OmrModel;
use App\Services\OmrModel\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Support\Facades\Log;
use Throwable;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
#[Queue('library')]
class GenerateOmrModelImage implements ShouldQueue
{
    use Queueable;

    protected Collection $omrModels;

    public function __construct(OmrModel|Collection $omrModels)
    {
        $this->omrModels = $omrModels instanceof OmrModel
            ? new Collection([$omrModels])
            : $omrModels;
    }

    /**
     * Render each model's image. A model that fails is logged and skipped so it can't block the rest of the batch.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $this->omrModels->each(function (OmrModel $model) use ($imageGenerator): void {
            try {
                $imageGenerator->regenerateImage($model);
            } catch (Throwable $e) {
                Log::error('OMR model image generation failed', ['omr_model_id' => $model->id, 'exception' => $e]);
            }
        });
    }
}
