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
        foreach (PartType::cases() as $dir) {
            if (!Storage::disk('images')->exists("library/official/{$dir->value}")) {
                Storage::disk('images')->makeDirectory("library/official/{$dir->value}");
            }
            if (!Storage::disk('images')->exists("library/unofficial/{$dir->value}")) {
                Storage::disk('images')->makeDirectory("library/unofficial/{$dir->value}");
            }
        }

        if (!Storage::disk('images')->exists('library/updates')) {
            Storage::disk('images')->makeDirectory('library/updates');
        }

    }
}
