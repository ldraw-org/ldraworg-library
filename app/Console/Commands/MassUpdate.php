<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MassUpdate extends Command
{
    protected $signature = 'lib:mass-update';

    protected $description = 'A command to do focused mass updates';

    /**
     * Losslessly optimize every texmap body, flagging changed official parts as minor edits.
     */
    public function handle(): void {}
}
