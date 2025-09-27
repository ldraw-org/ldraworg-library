<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\Enums\VoteType;
use App\Events\PartSubmitted;
use App\LDraw\LDrawFile;
use App\LDraw\Managers\Part\PartManager;
use App\LDraw\Managers\VoteManager;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Console\Command;

class MassUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:mass-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command to do focused mass updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Nothing yet
    }
}
