<?php

namespace App\Console\Commands;

use App\Services\LDraw\Managers\Part\PartManager;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ReloadSubparts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:reload-subparts {--o|official-only} {--u|unofficial-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload the subparts';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $lib = $this->option('official-only') ? 'official' : ($this->option('unofficial-only') ? 'unofficial' : 'all');
        $this->info("Reloading {$lib} subparts");
        Part::query()
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            )
            ->each(fn (Part $p) => app(PartManager::class)->loadSubparts($p));
        $this->info('Subpart reload complete');
    }
}
