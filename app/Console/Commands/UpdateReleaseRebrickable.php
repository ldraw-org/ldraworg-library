<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickable;
use App\Services\LDraw\Rebrickable;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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
    public function handle():void
    {
        $parts = Part::where(
            fn ($q) => $q->orWhere('marked_for_release', true)->orWhere('has_minor_edit', true)
        )
            ->canHaveRebrickablePart()
            ->get();
        $rb_part_nums = $parts
            ->pluck('rebrickable_part.number')
            ->merge($parts->pluck('sticker_rebrickable_part.number'))
            ->filter()
            ->unique();
        $rb_nums = new Collection();
        $rb_part_nums->chunk(500)
            ->each(function (Collection $r) use (&$rb_nums) {
                $rb = (new Rebrickable())->getParts(
                    [
                    'part_nums' => $r->implode(','),
                    'page_size' => 1000]
                );
                $rb_nums = $rb_nums->merge($rb->pluck('part_num'));
            });
        $rb_nums = $rb_nums->filter()->unique();
        if ($rb_part_nums->diff($rb_nums)->isEmpty()) {
            $this->info("All existing RB information is correct");
        } else {
            $parts->whereIn('rebrickable_part.number', $rb_part_nums->diff($rb_nums)->all())
                ->each(fn (Part $p, int $id): mixed => UpdateRebrickable::dispatch($p, true));
            $parts->whereIn('sticker_rebrickable_part.number', $rb_part_nums->diff($rb_nums)->all())
                ->each(fn (Part $p, int $id): mixed => UpdateRebrickable::dispatch($p, true));
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
        $parts->filter(fn (Part $p, int $id): bool => is_null($p->rebrickable_part))
            ->reject(fn (Part $p, int $id): bool => in_array(basename($p?->base_part->filename ?? ''), $reject_list))
            ->each(fn (Part $p, int $id): mixed => UpdateRebrickable::dispatch($p, true));
    }
}
