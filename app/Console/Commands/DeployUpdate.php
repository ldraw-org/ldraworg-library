<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Services\LDraw\Managers\Part\PartManager;
use Illuminate\Console\Command;

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
    public function handle(PartManager $partManager): void
    {
        Part::whereIn('category', [PartCategory::Sticker, PartCategory::StickerShortcut])
            ->each(fn (Part $part) => UpdateRebrickable::dispatch($part));
    }
}
