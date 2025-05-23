<?php

namespace App\Console\Commands;

use App\Jobs\PurgeOrphanImages;
use App\Jobs\UpdateRebrickable;
use App\LDraw\PartManager;
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
            Part::lazy()->each(fn (Part $p) => app(PartManager::class)->loadSubparts($p));

            $this->info('Recounting all votes');
            Part::unofficial()->lazy()->each(fn (Part $p) => $p->updatePartStatus());

            $this->info('Rechecking all unofficial parts');
            $this->call('lib:check', ['--unofficial-only' => true]);

            $this->info('Removing orphan keywords');
            PartKeyword::doesntHave('parts')->delete();


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
            $this->call('lib:update-colours');

            $this->info('Regenerate unofficial zip');
            $this->call('lib:refresh-zip');
        }

        $this->info('Removing orphan images');
        PurgeOrphanImages::dispatch();

        $this->info('Queueing missing images');
        $this->call('lib:render-parts', ['--unofficial-only' => true, '--missing' => true]);
        $this->call('lib:render-models', ['--missing' => true]);

        $this->info('Pruning failed jobs');
        $this->call('queue:prune-failed');
    }
}
