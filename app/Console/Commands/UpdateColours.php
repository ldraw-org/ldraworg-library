<?php

namespace App\Console\Commands;

use App\LDraw\LDrawColourManager;
use App\Models\LdrawColour;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
        app(LDrawColourManager::class)->importColours();
    }
}
