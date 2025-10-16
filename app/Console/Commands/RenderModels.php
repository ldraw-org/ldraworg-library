<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\Models\Omr\OmrModel;
use Illuminate\Console\Command;

class RenderModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:render-models {model?*} {--missing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Omr Model Images';

    /**
     * Execute the console command.
     */
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
                    UpdateImage::dispatch($m)->onQueue('maintenance');
                    $count++;
                }
            });
        $this->info("{$count} omr model images queued");
    }
}
