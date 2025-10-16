<?php

namespace App\Console\Commands;

use App\Settings\LibrarySettings;
use Illuminate\Console\Command;

class Tracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:tracker {--lock} {--unlock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'General Tracker operations';

    /**
     * Execute the console command.
     */
    public function handle(LibrarySettings $settings): void
    {
        if ($this->option('lock')) {
            $settings->tracker_locked = true;
            $this->info('Tracker is locked');
        } elseif ($this->option('unlock')) {
            $settings->tracker_locked = false;
            $this->info('Tracker is unlocked');
        }

        $settings->save();
    }
}
