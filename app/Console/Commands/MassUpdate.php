<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Events\PartRenamed;
use App\Jobs\UpdateZip;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Part\PartMover;
use Illuminate\Console\Command;

class MassUpdate extends Command
{
    protected $signature = 'lib:mass-update';

    protected $description = 'A command to do focused mass updates';

    public function handle(PartMover $partMover): void
    {
        $parts = Part::unofficial()
            ->whereRelation('subparts', 'id', 29962)
            ->partsFolderOnly()
            ->where('category', PartCategory::Sticker)
            ->where('filename', '<>', 'parts/s69434a.dat')
            ->get();
        $user = User::find(1);
        foreach ($parts as $index => $part) {
            $suffix = $this->indexToAlpha($index + 1);
            $oldname = $part->filename;
            $part->rebrickable_part_id = 101831;
            $partMover->moveUnofficialPart($part, newName: "parts/s69434{$suffix}.dat");

            PartRenamed::dispatch($part, $user, $oldname, $part->filename);
            UpdateZip::dispatch($part, $oldname);
        }
    }

    public function indexToAlpha(int $index): string
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr(($index % 26) + ord('a')).$letters;
            $index = intdiv($index, 26) - 1;
        }

        return $letters;
    }
}
