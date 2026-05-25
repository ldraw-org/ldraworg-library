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
        $this->info('Recounting all votes');
        Part::unofficial()->lazy()->each->updatePartStatus();
        $this->info('Vote recount complete');
    }
}
