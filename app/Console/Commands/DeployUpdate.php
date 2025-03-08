<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
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
    public function handle(): void
    {
        Part::whereIn('type', PartType::partsFolderTypes())
            ->whereDoesntHave('sticker_sheet')
            ->whereDoesntHave('parents')
            ->whereHas('subparts', fn ($query) => $query->where('category', PartCategory::Sticker))
            ->where('category', '!=', PartCategory::Sticker)
            ->each(function (Part $p){
                $p->category = PartCategory::StickerShortcut;
                $sticker = $p->subparts->where('category', PartCategory::Sticker)->first();
                $p->sticker_sheet()->associate($sticker->sticker_sheet);
                $p->generateHeader();
            });
    }
}
