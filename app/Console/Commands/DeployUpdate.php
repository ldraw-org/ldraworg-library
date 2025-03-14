<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use Illuminate\Console\Command;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $q = Part::canHaveExternalData();
        $this->info("Queueing {$q->count()} parts");
        $q->lazy()
            ->each(fn (Part $p) => UpdateRebrickable::dispatch($p));
        $this->info("Queueing complete");
    }
}
