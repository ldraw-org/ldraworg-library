<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Models\Part\Part;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class FixInitialSubmit extends Command
{
    protected $signature = 'lib:fix-initial-submit';

    protected $description = 'Find unofficial parts with no initial submit and add a creation date submit';

    public function handle(): void
    {
        Part::unofficial()
            ->whereDoesntHave('events', fn (Builder $query) => $query->where('initial_submit', true))
            ->each(function (Part $part) {
                $oldestEvent = $part->events()->unofficial()->oldest()->first();
                if (!is_null($oldestEvent) && $oldestEvent->event_type === EventType::Submit) {
                    $oldestEvent->initial_submit = true;
                    $oldestEvent->save();
                } elseif (!is_null($oldestEvent)) {
                    $part->events()->create([
                        'event_type' => EventType::Submit,
                        'user_id' => $part->user_id,
                        'initial_submit' => true,
                        'created_at' => $part->created_at <= $oldestEvent->created_at ? $part->created_at : $oldestEvent->created_at,
                    ]);
                } else {
                    $part->events()->create([
                        'event_type' => EventType::Submit,
                        'user_id' => $part->user_id,
                        'initial_submit' => true,
                        'created_at' => $part->created_at,
                    ]);
                }
            });
    }
}
