<?php

namespace App\Console\Commands;

use App\Enums\License;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Models\Part\Part;
use App\Models\User;
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
    }
}
