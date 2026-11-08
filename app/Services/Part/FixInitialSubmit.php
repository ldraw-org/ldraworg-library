<?php

namespace App\Services\Part;

use App\Enums\EventType;
use App\Models\Part\Part;
use Illuminate\Database\Eloquent\Builder;

class FixInitialSubmit
{
    public static function fixAll(): void
    {
        Part::unofficial()
            ->whereDoesntHave('events', fn (Builder $query) => $query->where('initial_submit', true))
            ->each(function (Part $part) {
                self::fixPart($part);
            });
    }

    public static function fixPart(Part $part): void
    {
        $oldestEvent = $part->events()->unofficial()->oldest()->first();
        if (! is_null($oldestEvent) && $oldestEvent->event_type === EventType::Submit) {
            $oldestEvent->initial_submit = true;
            $oldestEvent->save();
        } elseif (! is_null($oldestEvent)) {
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
    }
}
