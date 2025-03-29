<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\RebrickablePart;
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
        Part::whereJsonLength('rebrickable->data', '>', '0')
            ->each(function (Part $p) {
                $r = collect($p->rebrickable['data']);
                $part_num = basename($p->filename, '.dat');
                $rb_data = $r->where('part_num', $part_num)->isEmpty() ? $r->first() : $r->where('part_num', $part_num)->first();
                $rb = RebrickablePart::findOrCreateFromArray($rb_data);
                $p->rebrickable_part()->associate($rb);
                $p->save();
            });

        StickerSheet::whereJsonLength('rebrickable', '>', 0)
            ->each(function (StickerSheet $s) {
                $rb = RebrickablePart::findOrCreateFromArray($s->rebrickable->getArrayCopy());
                $s->rebrickable_part()->associate($rb);
                $s->save();
            });
    }
}
