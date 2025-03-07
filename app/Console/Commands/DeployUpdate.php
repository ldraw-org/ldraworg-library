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
        Part::with('part_category')
            ->whereIn('type', PartType::partsFolderTypes())
            ->lazy()
            ->each(function (Part $p){
                $p->category = PartCategory::from($p->part_category?->category);
                $p->save();
                if (!is_null($p->sticker_sheet_id) && $p->category != PartCategory::Sticker) {
                    $p->category = PartCategory::StickerShortcut;
                    $p->generateHeader();
                }
            });
    }
}
