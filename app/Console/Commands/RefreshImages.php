<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\Models\Part;
use Illuminate\Console\Command;

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
        switch ($this->option('lib')) {
            case 'official':
                $this->info('Queueing official part images');
                $parts = Part::official()->lazy();
                break;
            case 'unofficial':
                $this->info('Queueing unofficial part images');
                $parts = Part::unofficial()->lazy();
                break;
            default:
                $this->info('Queueing all part images');
                $parts = Part::lazy();
        }

        $parts->each(fn (Part $p) => UpdateImage::dispatch($p));
    }
}
