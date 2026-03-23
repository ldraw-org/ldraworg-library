<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\Cache\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh app cache after code update';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cache): void
    {
        $this->call('down');
        try {
            $this->call('optimize:clear');
            if (app()->environment('production')) {
                $this->call('optimize');
            }
            $cache->warmAll();
        } finally {
            $this->call('up');
        }
        $this->call('queue:restart');
    }
}
