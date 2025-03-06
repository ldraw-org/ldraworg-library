<?php

namespace App\Console\Commands;

use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\VoteType;
use App\Events\PartSubmitted;
use App\LDraw\PartManager;
use App\LDraw\VoteManager;
use App\Models\Part\Part;
use App\Models\Part\PartCategory;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Settings\LibrarySettings;
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
        $pm = app(PartManager::class);
        Part::whereIn('type', PartType::imageFormat())
            ->each(function (Part $p) {
                $p->has_minor_edit = true;
                $p->save();
            });
        Part::lazy()
            ->each(function (Part $p) use ($pm) {
                $pm->updateBasePart($p);
            });
    }
}
