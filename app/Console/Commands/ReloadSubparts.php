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
        Part::query()
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            )
            ->chunk(200, function ($parts) {
                \App\Jobs\ReloadSubparts::dispatch(
                    $parts->map(fn (Part $p) => $p->withoutRelations())
                )->onQueue('maintenance');
            });
        $this->info('Subparts queued for reload');
    }
}
