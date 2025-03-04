<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Enums\VoteType;
use App\Events\PartSubmitted;
use App\LDraw\PartManager;
use App\LDraw\VoteManager;
use App\Models\Part\Part;
use App\Models\Part\PartCategory;
use App\Models\User;
use Illuminate\Console\Command;

class MassUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:mass-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command to do focused mass updates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating category of all obsolete files in the parts folder');
        $pm = app(PartManager::class);
        $vm = app(VoteManager::class);
        $user = User::firstWhere('name', 'OrionP');
        $ob_cat = PartCategory::firstWhere('category', 'Obsolete');
        Part::where('description', 'LIKE', '%(Obsolete)')
            ->whereIn('type', PartType::partsFolderTypes())
            ->where('part_category_id', '!=', $ob_cat->id)
            ->doesntHave('unofficial_part')
            ->each(function (Part $part) use ($pm, $ob_cat, $user, $vm) {
                $up = $pm->copyOfficialToUnofficialPart($part);
                $up->category()->associate($ob_cat);
                $part->unofficial_part()->associate($up);
                $part->save();
                $up->history()->create([
                        'user_id' => $user->id,
                        'comment' => 'Change category to Obsolete'
                ]);
                $up->save();
                $up->refresh();
                $up->generateHeader();
                PartSubmitted::dispatch($up, $user);
                $vm->castVote($up, $user, VoteType::AdminFastTrack);
            });
    }
}
