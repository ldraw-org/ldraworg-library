<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReviewSummary\ReviewSummary;

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
        ReviewSummary::each(function (ReviewSummary $summary) {
           $summary->list = $summary->toString();
           $summary->save();
        });
    }
}
