<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RecheckParts extends Command
{
    protected $signature = 'lib:check {--lib=all}';

    protected $description = 'Recheck parts';

    public function handle()
    {
        $this->info("Rechecking {$this->option('lib')} parts");
        $manager = app(\App\LDraw\PartManager::class);
        $count = Part::when(
            $this->option('lib') == 'unofficial',
            fn (Builder $query) => $query->unofficial()
        )
            ->when(
                $this->option('lib') == 'official',
                fn (Builder $query) => $query->where(fn (Builder $query2) => $query2->official()->whereJsonLength('part_check_messages->errors', '>', 0))
            )
            ->count();
        $div = 50;
        $num = intdiv($count, $div) + 1;
        $iter = 1;
        Part::with('user', 'history', 'body', 'descendants', 'ancestors')
            ->when(
                $this->option('lib') == 'unofficial',
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('lib') == 'official',
                fn (Builder $query) => $query->where(fn (Builder $query2) => $query2->official()->whereJsonLength('part_check_messages->errors', '>', 0))
            )
            ->chunkById($div, function (Collection $parts) use ($manager, $num, &$iter) {
                $this->info("Processing chunk {$iter} of {$num}");
                foreach ($parts as $part) {
                    /** @var Part $part */
                    $manager->checkPart($part);
                }
                $iter += 1;
            });
    }
}
