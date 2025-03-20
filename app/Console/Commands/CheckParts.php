<?php

namespace App\Console\Commands;

use App\Jobs\CheckPart;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CheckParts extends Command
{
    protected $signature = 'lib:check {part?*} {--u|unofficial-only} {--o|official-only}';

    protected $description = 'Error check parts';

    public function handle()
    {
        $this->info("Queuing parts for error check");
        if ($this->argument('part')) {
            $q = Part::whereIn('id', $this->argument('part'));
            $count = $q->count();
            if ($count > 0) {
                CheckPart::dispatch($q->get());
            }
        } else {
            $q = Part::with('user', 'history', 'body', 'descendants', 'ancestors')
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            );
            $count = $q->count();
            if ($count > 0) {
                $q->cursor()->each(fn (Part $part)=> CheckPart::dispatch($part));
            }
        }
        $this->info("{$count} parts queued for error check");
    }
}
