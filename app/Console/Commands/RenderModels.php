<?php

namespace App\Console\Commands;

use App\Jobs\GenerateOmrModelImage;
use App\Models\Omr\OmrModel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RenderModels extends Command
{
    protected $signature = 'lib:render-models {--M|missing}';

    protected $description = 'Refresh Omr Model Images';

    public function handle(): void
    {

        $this->info("Queueing omr model images");
        $onlyMissing = $this->option('missing');
        OmrModel::query()
            ->chunk(200, function ($models) use ($onlyMissing) {
                GenerateOmrModelImage::dispatch(
                    $models->map(fn (OmrModel $m) => $m->withoutRelations()),
                    $onlyMissing
                )->onQueue('maintenance');
            });
    }
}
