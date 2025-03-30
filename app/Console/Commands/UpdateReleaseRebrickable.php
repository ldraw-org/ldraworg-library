<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickable;
use App\LDraw\Rebrickable;
use App\Models\Part\Part;
use Illuminate\Console\Command;

class UpdateReleaseRebrickable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:release-rb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do a pre release RB update';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parts = Part::canHaveRebrickablePart()
            ->doesntHave('sticker_sheet')
            ->where(
                    fn ($q) => $q->orWhere('marked_for_release', true)->orWhere('has_minor_edit', true)
            )
            ->get();
        $rb_part_nums = $parts
            ->pluck('rebrickable_part.number')
            ->filter()
            ->unique();
        $rb = (new Rebrickable)->getParts([
            'part_nums' => $rb_part_nums->implode(','),
            'page_size' => 1000]
        );
        $rb_nums = $rb->pluck('part_num');
        if ($rb_part_nums->count() == $rb_nums->count()) {
            $this->info("All existing RB information is correct");
        } else {
            $parts->whereIn('rebrickable_part.number', $rb_part_nums->diff($rb_nums)->all())
                ->each(fn (Part $p) => UpdateRebrickable::dispatch($p, true));

        }
        $reject_list = [
            '973.dat',
            '16000.dat',
            '16001.dat',
            '3818.dat',
            '3819.dat',
            '20460.dat',
            '20461.dat',
            '3816.dat',
            '3817.dat',
        ];
        $parts->whereNull('rebrickable_part_id')
            ->reject(fn (Part $p) => in_array(basename($p?->base_part->filename ?? ''), $reject_list))
            ->each(fn (Part $p) => UpdateRebrickable::dispatch($p, true));
    }
}
