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
        if ($this->option('optimize')) {
            $this->call('down');
            try {
                $this->call('optimize:clear');
                if (app()->environment('production')) {
                    $this->call('optimize');
                }
            } finally {
                $this->call('up');
            }
        }
        $cache->warmAll();
        if (config('queue.default') == 'redis') {
            $this->call('horizon:terminate');
        } else {
            $this->call('queue:restart');
        }

    }
}
