<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Services\Part\SyncSubparts;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ReloadSubparts extends Command
{
    protected $signature = 'lib:reload-subparts {--o|official-only} {--u|unofficial-only}';

    protected $description = 'Reload the subparts';

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
            ->each(fn (Part $p) => app(SyncSubparts::class)->loadSubparts($p));
        $this->info('Subpart reload complete');
    }
}
