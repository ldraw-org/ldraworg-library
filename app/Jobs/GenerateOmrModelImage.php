<?php

namespace App\Jobs;

use App\Models\Omr\OmrModel;
use App\Services\OmrModel\ImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;

#[DeleteWhenMissingModels]
#[Timeout(1800)]
class GenerateOmrModelImage implements ShouldQueue
{
    use Queueable;

    protected Collection $omrModels;

    public function __construct(
        OmrModel|Collection $omrModels,
        protected bool $onlyMissing = false
    )
    {
        $this->omrModels = $omrModels instanceof OmrModel
            ? new Collection([$omrModels])
            : $omrModels;

    }

    /**
     * Execute the job.
     */
    public function handle(ImageGenerator $imageGenerator): void
    {
        $this->omrModels
            ->reject(fn (OmrModel $part) => $this->onlyMissing && file_exists($part->getFirstMediaPath('image')))
            ->each(fn (OmrModel $part) => $imageGenerator->regenerateImage($part));
    }
}
