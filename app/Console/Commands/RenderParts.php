<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class RenderParts extends Command
{
    protected $signature = 'lib:render-parts {--o|official-only} {--u|unofficial-only} {--M|missing}';

    protected $description = 'Refresh Library Images';

    /**
     * Queue part images in batches. With --missing, only parts whose image file doesn't exist are queued.
     */
    public function handle(): void
    {
        $onlyMissing = (bool) $this->option('missing');
        $queued = 0;

        Part::query()
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            )
            ->when($onlyMissing, fn (Builder $query) => $query->with('media'))
            ->lazyById(500)
            ->when($onlyMissing, fn (LazyCollection $parts) => $parts->reject(
                fn (Part $part): bool => file_exists((string) $part->getFirstMedia('image')?->getPath())
            ))
            ->chunk(200)
            ->each(function (LazyCollection $parts) use (&$queued): void {
                GeneratePartImage::dispatch(new Collection($parts->map->withoutRelations()->values()->all()))
                    ->onQueue('maintenance');
                $queued += $parts->count();
            });

        $this->info("Queued {$queued} part images");
    }
}
