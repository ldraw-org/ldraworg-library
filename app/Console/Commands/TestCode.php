<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\LDraw\Managers\Part\PartReleaseManager;

class TestCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parts = Part::unofficial()->has('official_part')->where('marked_for_release', true)->get();
        $updater = new PartReleaseManager($parts, User::find(1), true, []);
        $updater->createRelease();
    }
}
