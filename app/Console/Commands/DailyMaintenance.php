<?php

namespace App\Console\Commands;

use App\Jobs\PurgeOrphanImages;
use App\Jobs\UpdateRebrickable;
use App\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\RebrickablePart;
use Illuminate\Console\Command;

class DailyMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:daily-maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Daily Maintenance';

    /**
     * Execute the console command.
     */
    public function handle()
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

            $this->info('Removing orphan keywords');
            PartKeyword::doesntHave('parts')->delete();

            $this->info('Ensuring initial submit event exists');
            $this->call('lib:fix-initial-submit');

            $this->info('Refreshing Rebrickable data');
            Part::canHaveRebrickablePart()
                ->where(
                    fn ($query) =>
                    $query
                        ->orwhereBetween('created_at', [now(), now()->subDay()])
                        ->orWhereBetween('created_at', [now()->subWeek()->subDay(), now()->subWeek()])
                        ->orWhereBetween('created_at', [now()->subMonth()->subDay(), now()->subMonth()])
                )
                ->each(fn (Part $part) => UpdateRebrickable::dispatch($part));

            $this->info('Removing orphan rebrickable parts');
            RebrickablePart::doesntHave('parts')->doesntHave('sticker_sheets')->delete();

            $this->info('Reloading colors for LDConfig');
            $this->call('lib:update-ldconfig');

            $this->info('Regenerate unofficial zip');
            $this->call('lib:refresh-zip');

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
