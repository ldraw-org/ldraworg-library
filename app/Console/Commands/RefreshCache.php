<?php

namespace App\Console\Commands;

use App\Services\Cache\CacheService;
use Illuminate\Console\Command;

class RefreshCache extends Command
{
    protected $signature = 'lib:cache';

    protected $description = 'Refresh app cache after code update';

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
        $this->call('horizon:terminate');
    }
}
