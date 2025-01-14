<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\StickerSheet;
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
        // Preserve this code for use later
        /*
        $sites = ['bricklink' => null, 'brickowl' => null, 'lego id' => null, 'brickset' => null];
        Part::query()->update(['external_ids' => $sites]);
        foreach($sites as $site => $value) {
            PartKeyword::has('parts')->where('keyword', 'LIKE', "{$site} %")
            ->each(function (PartKeyword $kw) use ($site) {
                $number = str_replace("{$site} ", '', strtolower($kw->keyword));
                //$this->info($number);
                $kw->parts()->update(["external_ids->{$site}" => $number]);
            });
        }
        */
        Part::query()->update(['sticker_sheet_id' => null]);
        StickerSheet::query()->delete();
        $numbers = Part::whereRelation('category', 'category', 'Sticker')
            ->partsFolderOnly()
            ->pluck('filename')
            ->transform(function (string $name) {
                $m = preg_match('#^parts\/([0-9]+)[a-z0-9]+(?:c[0-9]{2})?\.dat$#iu', $name, $s);
                if ($m === 1) {
                    return $s[1];
                }
                return 'unknown';
            })
            ->unique();

        $sm = app(\App\LDraw\StickerSheetManager::class);
        foreach($numbers as $number) {
            $sm->addStickerSheet($number);
        }
        $sheets = StickerSheet::all();
        Part::whereRelation('category', 'category', 'Sticker')
            ->partsFolderOnly()
            ->each(function (Part $p) use ($sheets) {
                $m = preg_match('#^parts\/([0-9]+)[a-z0-9]+(?:c[0-9]{2})?\.dat$#iu', $p->filename, $s);
                if ($m === 1) {
                    $p->sticker_sheet_id = $sheets->where('number', $s[1])->first()->id;
                } else {
                    $p->sticker_sheet_id = $sheets->where('number', 'unknown')->first()->id;
                }
                $p->save();
            });
        Part::whereRelation('category', 'category', 'Sticker Shortcut')
            ->each(function (Part $p) {
                $p->sticker_sheet_id = $p->subparts->where('category.category', 'Sticker')->first()?->sticker_sheet_id;
                $p->save();
            });
        foreach($sheets as $sheet) {
            $sheet->load('parts');
            $sheet->rebrickable = $sm->getRebrickableData($sheet);
            $sheet->save();
            if (is_null($sheet->rebrickable)) {
                $this->info("No Rebrickable data for {$sheet->number}");
            }
        }
    }
}
