<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Console\Command;

class DailyMaintenance extends Command
{
    protected $signature = 'lib:daily-maintenance';

    protected $description = 'Run Daily Maintenance';

    public function handle(): void
    {
        // None of these need to happen daily on the dev server
        // and just soaks up resources.
        if (app()->environment('production')) {
            $this->info('Reloading all subparts');
            $this->call('lib:reload-subparts');

            $this->info('Recounting all votes');
            $this->call('lib:recount-votes');

            $this->info('Rechecking all unofficial parts');
            $this->call('lib:check', ['--unofficial-only' => true]);

            $this->info('Resyncing part fixes');
            $this->call('lib:sync-fixes');

            if (PartKeyword::doesntHave('parts')->exists()) {
                $this->info('Removing orphan keywords');
                PartKeyword::doesntHave('parts')->delete();
            }

            $this->info('Ensuring initial submit event exists');
            $this->call('lib:fix-initial-submit');

            $this->info('Queue update of uonfficial parts without rebrickable data');
            $this->call('lib:update-rebrickable');

            $this->info('Reloading colors for LDConfig');
            $this->call('lib:update-ldconfig');

            $this->info('Regenerate unofficial zip');
            $this->call('lib:refresh-zip');

            $this->info('Regenerate ptreleases files');
            $this->call('lib:refresh-ptreleases');

            $this->info('Regenerate categories.txt');
            $this->call('lib:refresh-categories-txt');

            $this->info('Regenerate library.csv');
            $this->call('lib:refresh-csv');

            $this->info('Queueing missing images');
            $this->call('lib:render-parts', ['--missing' => true]);
            $this->call('lib:render-models', ['--missing' => true]);
        } else {
            $this->info('Queueing missing images');
            $this->call('lib:render-parts', ['--unofficial-only', '--missing' => true]);
        }

        $this->info('Pruning failed jobs');
        $this->call('queue:prune-failed');
    }
}
