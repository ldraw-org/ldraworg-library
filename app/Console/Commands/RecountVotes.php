<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Command;

class RecountVotes extends Command
{
    protected $signature = 'lib:recount-votes';

    protected $description = 'Command description';

    public function handle(): void
    {
        \App\Jobs\RecountVotes::dispatch();
        $this->info('Votes queued for recount');
    }
}
