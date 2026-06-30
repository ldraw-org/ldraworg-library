<?php

namespace App\Console\Commands;

use App\Jobs\GenerateOmrModelImage;
use App\Models\Omr\OmrModel;
use Illuminate\Console\Command;

class RenderModels extends Command
{
    protected $signature = 'lib:render-models {model?*} {--missing}';

    protected $description = 'Refresh Omr Model Images';

    public function handle(): void
    {

        $this->info("Queueing omr model images");
        if ($this->argument('model')) {
            $models = OmrModel::whereIn('id', $this->argument('model'));
        } else {
            $models = OmrModel::query();
        }

        $count = 0;
        $onlyMissing = $this->option('missing');
        $models
            ->lazy()
            ->each(function (OmrModel $m) use (&$count, $onlyMissing) {
                if (!$onlyMissing || !file_exists($m->getFirstMediaPath('image'))) {
                    GenerateOmrModelImage::dispatch($m->id)->onQueue('maintenance');
                    $count++;
                }
            });
        $this->info("{$count} omr model images queued");
    }
}
