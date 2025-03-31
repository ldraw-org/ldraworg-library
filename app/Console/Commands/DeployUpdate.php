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
        StickerSheet::whereJsonLength('rebrickable', '>', 0)
        ->each(function (StickerSheet $s) {
            $rb = RebrickablePart::findOrCreateFromArray($s->rebrickable->getArrayCopy());
            if (is_null($rb)) { dd($s); }
            $s->rebrickable_part()->associate($rb);
            $s->save();
        });
    }
}
