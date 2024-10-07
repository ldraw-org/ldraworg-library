<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\LDraw\OmrModelManager;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Settings\LibrarySettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!Storage::disk('images')->exists('omr/models')) {
            Storage::disk('images')->makeDirectory('omr/models');
        }
        Set::each(function (Set $s) {
            $s->models->each(fn (OmrModel $m) => UpdateImage::dispatch($m));
        });
    }
}
