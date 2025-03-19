<?php

namespace App\Console\Commands;

use App\Models\Part\PartEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
        PartEvent::whereNotNull('comment')
            ->each(function (PartEvent $e) {
                $comment = Str::of($e->comment)->trim()->toString();
                if ($comment == '') {
                    $e->comment = null;
                    $e->save();
                }
            });
    }
}
