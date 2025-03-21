<?php

namespace App\Console\Commands;

use App\Enums\PartError;
use App\Models\Part\Part;
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
        Part::hasError('keyword.partternset')
            ->cursor()
            ->each(function (Part $part) {
                $m = $part->part_check_messages;
                unset($m['errors']['keyword.partternset']);
                $m['errors'][PartError::NoSetKeywordForPattern->value] = [];
                $part->part_check_messages = $m;
                $part->save();
            });
    }
}
