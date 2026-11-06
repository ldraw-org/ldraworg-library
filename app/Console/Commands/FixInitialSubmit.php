<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class FixInitialSubmit extends Command
{
    protected $signature = 'lib:fix-initial-submit';

    protected $description = 'Find unofficial parts with no initial submit and add a creation date submit';

    public function handle(): void
    {
        \App\Jobs\FixInitialSubmit::dispatch();
    }
}
