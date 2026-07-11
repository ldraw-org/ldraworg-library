<?php

namespace App\Console\Commands;

use App\Jobs\RefreshCategoriesTxt as RefreshCategoriesTxtJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:refresh-categories-txt')]
#[Description('Refresh categories.txt')]
class RefreshCategoriesTxt extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        RefreshCategoriesTxtJob::dispatch();
    }
}
