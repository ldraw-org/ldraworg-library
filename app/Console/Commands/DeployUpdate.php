<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
        Part::canHaveRebrickablePart()
            ->has('rebrickable_part')
            ->each(function (Part $p) {
                $p->setExternalSiteKeywords('true');
            });
    }
}
