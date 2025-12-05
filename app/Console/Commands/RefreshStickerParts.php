<?php

namespace App\Console\Commands;

use App\Jobs\UpdateRebrickableStickerParts;
use Illuminate\Console\Command;

class RefreshStickerParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-sticker-parts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        UpdateRebrickableStickerParts::dispatch();
        $this->info('Sticker part queued fro refresh');
    }
}
