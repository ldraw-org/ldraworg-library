<?php

namespace App\Console\Commands;

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
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
    public function handle(): void
    {
        $this->call('down');
        try {
            $this->call('optimize:clear');
            Cache::remember(
                'part_release_current',
                now()->addDays(30),
                fn () => PartRelease::current()
            );
            Cache::remember(
                'current_official_part_count',
                now()->addDays(30),
                fn () => Part::official()
                    ->where('type', PartType::Part)
                    ->whereNull('type_qualifier')
                    ->activeParts()
                    ->count()
            );
            $this->call('lib:update-ldconfig');
            $this->call('optimize');
        } finally {
            $this->call('up');
        }
        $this->call('queue:restart');
    }
}
