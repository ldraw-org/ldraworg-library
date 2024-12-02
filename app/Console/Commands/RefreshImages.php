<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RefreshImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:refresh-images {--lib=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Library Images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Queueing {$this->option('lib')} part images");
        Part::when(
                $this->option('lib') == 'unofficial',
                fn (Builder $query) => $query->unofficial()
            )
            ->when(
                $this->option('lib') == 'official',
                fn (Builder $query) => $query->official()
            )
            ->lazy()
            ->each(fn (Part $p) => UpdateImage::dispatch($p));
    }
}
