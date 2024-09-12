<?php

namespace App\Console\Commands;

use App\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RecheckParts extends Command
{
    protected string $signature = 'pt:recheck';

    protected string $description = 'Recheck all parts';

    public function handle()
    {
        $manager = app(\App\LDraw\PartManager::class);
        $count = Part::count();
        $div = 50;
        $num = intdiv($count, $div) + 1;
        $iter = 1;
        Part::chunkById($div, function (Collection $parts) use ($manager, $num, &$iter) {
            $this->info("Processing chunk {$iter} of {$num}");
            foreach($parts as $part) {
                /** @var Part $part */
                $manager->checkPart($part);
            }
            $iter += 1;
        });
    }
}
