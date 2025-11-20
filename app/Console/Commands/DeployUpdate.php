<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use App\Services\LDraw\Rebrickable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
    public function handle(Rebrickable $rebrickable): void
    {
        $stickers = Cache::get('rebrickable_stickers', fn () => $rebrickable->getParts(['part_cat_id' => 58, 'page_size' => 1000]));
        
        $stickers->each(function (array $sticker) {
            RebrickablePart::updateOrCreateFromArray($sticker);
        });

        StickerSheet::with('rebrickable_part', 'parts')
            ->each(function (StickerSheet $sheet) {
                if (!is_null($sheet->rebrickable_part_id)) {
                    $rbPart = $sheet->rebrickable_part;
                    $rbPart->ldraw_number = $sheet->number;
                    $sheet->rebrickable_part->save();
                } else {
                    $rbPart = RebrickablePart::create([
                        'number' => "u-{$sheet->number}",
                        'name' => "Sticker sheet {$sheet->number}",
                        'rb_part_category_id' => 58,
                        'is_local' => true,
                        'ldraw_number' => $sheet->number,
                    ]);
                }
                $partIds = $sheet->parts->pluck('id');
                Part::whereIn('id', $partIds)->update(['rebrickable_part_id' => $rbPart->id]);
           });
    }
}
