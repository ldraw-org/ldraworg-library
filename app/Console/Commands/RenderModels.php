<?php

namespace App\Console\Commands;

use App\Jobs\GenerateOmrModelImage;
use App\Models\Omr\OmrModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class RenderModels extends Command
{
    protected $signature = 'lib:render-models {--M|missing}';

    protected $description = 'Refresh Omr Model Images';

    /**
     * Queue OMR model images in batches. With --missing, only models whose image file doesn't exist are queued.
     */
    public function handle(): void
    {
        $onlyMissing = (bool) $this->option('missing');
        $queued = 0;

        OmrModel::query()
            ->lazyById(500)
            ->when($onlyMissing, fn (LazyCollection $models) => $models->reject(
                fn (OmrModel $model): bool => file_exists((string) $model->getFirstMedia('image')?->getPath())
            ))
            ->chunk(50)
            ->each(function (LazyCollection $models) use (&$queued): void {
                GenerateOmrModelImage::dispatch(new Collection($models->map->withoutRelations()->values()->all()))
                    ->onQueue('maintenance');
                $queued += $models->count();
            });

        $this->info("Queued {$queued} OMR model images");
    }
}
