<?php

namespace App\Console\Commands;

use App\Models\RebrickablePart;
use App\Services\LDraw\Managers\StickerSheetManager;
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
    public function handle(StickerSheetManager $manager): void
    {
        $sheets = RebrickablePart::sticker_sheets()
          ->where('is_local', false)
          ->get();

        foreach ($sheets as $sheet) {
            dispatch(function () use ($sheet, $manager) {
                $manager->refreshStickerSets($sheet);
            });
        }
    }
}
