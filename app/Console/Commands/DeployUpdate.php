<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\StickerSheet;
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
