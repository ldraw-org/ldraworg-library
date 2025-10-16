<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Command;

class RecountVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:recount-votes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Recounting all votes');
        Part::unofficial()->lazy()->each->updatePartStatus();
        $this->info('Vote recount complete');
    }
}
