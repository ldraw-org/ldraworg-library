<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class FixInitialSubmit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:fix-initial-submit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find unofficial parts with no initial submit and add a creation date submit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Part::unofficial()
            ->whereDoesntHave('events', fn (Builder $query) => $query->where('initial_submit', true))
            ->each(function (Part $part) {
                $oldestEvent = $part->events()->unofficial()->oldest()->first();
                if (!is_null($oldestEvent) && $oldestEvent->event_type === EventType::Submit) {
                    $oldestEvent->initial_submit = true;
                    $oldestEvent->save();
                } else {
                    $part->events()->create([
                        'event_type' => EventType::Submit,
                        'user_id' => $part->user_id,
                        'initial_submit' => true,
                        'created_at' => $part->created_at <= $oldestEvent->created_at ? $part->created_at : $oldestEvent->created_at,
                    ]);
                }
            });
    }
}
