<?php

namespace App\Console\Commands;

use App\Jobs\CheckPart;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CheckParts extends Command
{
    protected $signature = 'lib:check {--u|unofficial-only} {--o|official-only}';

    protected $description = 'Error check parts';

    public function handle(): void
    {
        $this->info("Queuing parts for error check");
        Part::query()
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            )
            ->chunk(200, fn (Collection $parts) => CheckPart::dispatch($parts)->onQueue('maintenance'));
    }
}
