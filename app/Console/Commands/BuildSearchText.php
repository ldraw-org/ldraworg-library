<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BuildSearchText as BuildSearchTextJob;
class BuildSearchText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:build-search-text';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build/Refresh Search Text';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        BuildSearchTextJob::dispatch()->onQueue('maintenance');
    }
}
