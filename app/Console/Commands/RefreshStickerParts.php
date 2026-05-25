<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickableStickerParts;
use Illuminate\Console\Command;

class RefreshStickerParts extends Command
{
    protected $signature = 'lib:refresh-sticker-parts';

    protected $description = 'Command description';

    public function handle(): void
    {
        UpdateRebrickableStickerParts::dispatch();
        $this->info('Sticker parts queued for refresh');
    }
}
