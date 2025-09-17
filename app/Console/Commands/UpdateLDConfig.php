<?php

namespace App\Console\Commands;

use App\LDraw\Managers\LDConfigManager;
use Illuminate\Console\Command;

class UpdateLDConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update-ldconfig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update colours and avatars from the current LDConfig';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(LDConfigManager::class)->importColours();
        app(LDConfigManager::class)->importAvatars();
    }
}
