<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UpdateConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update or refresh the configuration values for the library';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Storage::disk('library')->exists('official')) {
            Storage::disk('library')->makeDirectory('official');
        }

        if (!Storage::disk('library')->exists('official/models')) {
            Storage::disk('library')->makeDirectory('official/models');
        }

        if (!Storage::disk('library')->exists('unofficial')) {
            Storage::disk('library')->makeDirectory('unofficial');
        }

        if (!Storage::disk('library')->exists('updates')) {
            Storage::disk('library')->makeDirectory('updates');
        }

        if (!Storage::disk('library')->exists('omr')) {
            Storage::disk('library')->makeDirectory('omr');
        }

        $this->call('lib:refresh-zip');

    }
}
