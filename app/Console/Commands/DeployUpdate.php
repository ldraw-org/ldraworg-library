<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Enums\VoteType;
use App\Models\Part\PartEvent;
use App\Models\Vote;
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
        Vote::each(function (Vote $v) {
            $v->vote_type = VoteType::from($v->vote_type_code);
            $v->save();
        });
        PartEvent::each(function (PartEvent $e) {
            $e->vote_type = VoteType::tryFrom($e->vote_type_code);
            $e->event_type = EventType::from($e->part_event_type->slug);
            $e->save();
        });
    }
}
