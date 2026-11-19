<?php

namespace App\Console\Commands;

use App\Services\Omr\Set\SyncSetMetadata;
use App\Services\Omr\Theme\SyncRebrickableThemes;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:update-omr')]
#[Description('Update OMR themes and set details from the Rebrickable CSV downloads')]
class UpdateOmrData extends Command
{
    public function handle(SyncRebrickableThemes $syncThemes, SyncSetMetadata $syncSets): void
    {
        $syncThemes->handle();
        $this->info('Themes synced');

        $this->info("{$syncSets->handle()} sets updated");
    }
}
