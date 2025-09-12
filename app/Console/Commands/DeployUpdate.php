<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
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
        Part::whereNotIn('category', [PartCategory::Sticker, PartCategory::StickerShortcut])
            ->update(['sticker_sheet_id' => null]);
        Part::where('category', PartCategory::Moved)
            ->has('keywords')
            ->lazy()
            ->each(function (Part $part) {
                $part->keywords()->sync([]);
                $part->generateHeader();
                if ($part->isOfficial()) {
                    $part->has_minor_edit = true;
                    $part->save();
                }
            });
    }
}
