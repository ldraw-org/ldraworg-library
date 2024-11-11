<?php

namespace App\Console\Commands;

use App\LDraw\LDrawColourManager;
use Illuminate\Console\Command;

class UpdateColours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update-colours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update colour from the current LDConfig';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LDrawColourManager::importColours();
    }
}
