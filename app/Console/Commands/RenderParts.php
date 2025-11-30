<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RenderParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:render-parts {part?*} {--o|official-only} {--u|unofficial-only} {--M|missing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Library Images';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info("Queueing part images");
        if ($this->argument('part')) {
            $parts = Part::whereIn('id', $this->argument('part'));
        } else {
            $parts = Part::query()
                ->when(
                    $this->option('unofficial-only') && !$this->option('official-only'),
                    fn (Builder $query) => $query->unofficial()
                )
                ->when(
                    $this->option('official-only') && !$this->option('unofficial-only'),
                    fn (Builder $query) => $query->official()
                );
        }
        $count = 0;
        $onlyMissing = $this->option('missing');
        $parts
            ->lazy()
            ->each(function (Part $p) use (&$count, $onlyMissing) {
                if (!$onlyMissing || !file_exists($p->getFirstMediaPath('image'))) {
                    UpdateImage::dispatch($p)->onQueue('maintenance');
                    $count++;
                }
            });
        $this->info("{$count} part images queued");
    }
}
