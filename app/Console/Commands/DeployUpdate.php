<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployUpdate extends Command
{
    protected $signature = 'lib:update';

    protected $description = 'Update the app after update deployments';

    public function handle(): void
    {
    }
}
