<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RenderParts extends Command
{
    protected $signature = 'lib:render-parts {--o|official-only} {--u|unofficial-only} {--M|missing}';

    protected $description = 'Refresh Library Images';

    public function handle(): void
    {
        $this->info('Queueing part images');

        $onlyMissing = $this->option('missing');
        Part::query()
            ->when(
                $this->option('unofficial-only') && !$this->option('official-only'),
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('official-only') && !$this->option('unofficial-only'),
                fn (Builder $query) => $query->official()
            )
            ->chunk(200, function ($parts) use ($onlyMissing) {
                GeneratePartImage::dispatch(
                    $parts->map(fn (Part $p) => $p->withoutRelations()),
                    $onlyMissing
                )->onQueue('maintenance');
            });
    }
}
