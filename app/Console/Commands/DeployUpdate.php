<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
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
        $rb = new Rebrickable();
        Part::has('rebrickable_part')
            ->hasError(PartError::NoSetKeywordForPattern)
            ->each(function (Part $part) use ($rb) {
                $rb_num = $part->rebrickable_part->number;
                $set = $rb->getPartColorSets($rb_num, $rb->getPartColor($rb_num, true)->pluck('color_id')->first() ?? '', true)->pluck('set_num')->first() ?? '';
                if ($set == '') {
                    $this->info("Set not found for {$part->name()} using RB part {$rb_num}");
                    return;
                }
                if (Str::of($set)->endsWith(['-1', '-2'])) {
                    $set = substr($set, 0, -2);
                }
                $kw = PartKeyword::firstOrCreate([
                    'keyword' => "Set {$set}"
                ]);
                $part->keywords()->syncWithoutDetaching([$kw]);
                $part->load('keywords');
                if ($part->isOfficial()) {
                    $part->has_minor_edit = true;
                }
                unset($part->part_check_messages['errors'][PartError::NoSetKeywordForPattern->value]);
                $part->generateHeader();
            });
    }
}
