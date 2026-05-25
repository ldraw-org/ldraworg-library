<?php

namespace App\Console\Commands;

use App\Services\LDraw\Managers\LDConfigManager;
use Illuminate\Console\Command;

class UpdateLDConfig extends Command
{
    protected $signature = 'lib:update-ldconfig';

    protected $description = 'Update colours and avatars from the current LDConfig';

    public function handle(LDConfigManager $manager): void
    {
        $manager->importColours();
        $manager->importAvatars();
    }
}
