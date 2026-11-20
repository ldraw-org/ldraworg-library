<?php

namespace App\Console\Commands;

use App\Services\Cache\CacheService;
use Illuminate\Console\Command;

class RefreshCache extends Command
{
    protected $signature = 'lib:cache {--o|optimize}';

    protected $description = 'Refresh app cache after code update';

    public function handle(CacheService $cache): void
    {
        $this->call('down');
        if ($this->option('optimize')) {
            $this->call('optimize:clear');
            if (app()->environment('production')) {
                $this->call('optimize');
            }
        }
        if (config('queue.default') == 'redis') {
            $this->call('horizon:terminate');
        } else {
            $this->call('queue:restart');
        }
        $this->call('up');
        $cache->warmAll();
    }
}
