<?php

namespace App\Console\Commands;

use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use Illuminate\Console\Command;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sites = ['bricklink' => null, 'brickowl' => null, 'lego id' => null, 'brickset' => null];
        Part::query()->update(['external_ids' => $sites]);
        foreach($sites as $site => $value) {
            PartKeyword::has('parts')->where('keyword', 'LIKE', "{$site} %")
            ->each(function (PartKeyword $kw) use ($site) {
                $number = str_replace("{$site} ", '', strtolower($kw->keyword));
                //$this->info($number);
                $kw->parts()->update(["external_ids->{$site}" => $number]);
            });
        }
    }
}
