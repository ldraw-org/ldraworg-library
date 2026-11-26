<?php

namespace App\Console\Commands;

use App\Services\LDConfig\AvatarImporter;
use App\Services\LDConfig\ColourImporter;
use Illuminate\Console\Command;

class UpdateLDConfig extends Command
{
    protected $signature = 'lib:update-ldconfig';

    protected $description = 'Update colours and avatars from the current LDConfig';

    public function handle(ColourImporter $colours, AvatarImporter $avatars): void
    {
        $colours->import();
        $avatars->import();
    }
}
