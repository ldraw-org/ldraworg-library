<?php

namespace App\Console\Commands;

use App\LDraw\LibraryConfig;
use App\Models\Part\PartCategory;
use App\Models\Part\PartEventType;
use App\Models\Part\PartLicense;
use App\Models\Part\PartType;
use App\Models\Part\PartTypeQualifier;
use App\Models\VoteType;
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
        foreach (LibraryConfig::partCategories() as $category) {
            PartCategory::updateOrCreate(
                ['category' => $category['category']],
                $category
            );
        }

        foreach (PartType::getDirectories() as $dir) {
            $dir = substr($dir, 0, -1);
            if (!Storage::disk('images')->exists("library/official/{$dir}")) {
                Storage::disk('images')->makeDirectory("library/official/{$dir}");
            }
            if (!Storage::disk('images')->exists("library/unofficial/{$dir}")) {
                Storage::disk('images')->makeDirectory("library/unofficial/{$dir}");
            }
        }

        if (!Storage::disk('images')->exists('library/updates')) {
            Storage::disk('images')->makeDirectory('library/updates');
        }

    }
}
