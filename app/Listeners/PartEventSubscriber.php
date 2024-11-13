<?php

namespace App\Listeners;

use App\Events\PartComment;
use App\Events\PartDeleted;
use App\Events\PartHeaderEdited;
use App\Events\PartReleased;
use App\Events\PartRenamed;
use App\Events\PartReviewed;
use App\Events\PartSubmitted;
use App\Events\PartUpdateProcessingComplete;
use App\Models\Part\PartEvent;
use Illuminate\Events\Dispatcher;

class PartEventSubscriber
{
    public function storeSubmitPartEvent(PartSubmitted $event): void
    {
        $init_submit = is_null(PartEvent::unofficial()->firstWhere('part_id', $event->part->id));
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'submit')->id,
            'initial_submit' => $init_submit,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'comment' => $event->comment,
        ]);
    }

    public function storeRenamePartEvent(PartRenamed $event): void
    {
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'rename')->id,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'moved_to_filename' => $event->moved_to,
            'moved_from_filename' => $event->moved_from,
        ]);
    }

    public function storePartHeaderEditEvent(PartHeaderEdited $event): void
    {
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'edit')->id,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'header_changes' => $event->changes,
            'comment' => $event->comment,
        ]);
    }

    public function storePartReleaseEvent(PartReleased $event): void
    {
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'release')->id,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'part_release_id' => $event->release->id,
            'comment' => "Release {$event->release->name}",
        ]);
    }

    public function storePartReviewEvent(PartReviewed $event): void
    {
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'review')->id,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'vote_type_code' => $event->vote_type_code,
            'comment' => $event->comment,
        ]);
    }

    public function storePartCommentEvent(PartComment $event): void
    {
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'comment')->id,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'comment' => $event->comment,
        ]);
    }

    public function storePartDeletedEvent(PartDeleted $event): void
    {
        \App\Models\Part\Part::whereIn('id', $event->parentIds)->each(function (\App\Models\Part\Part $p) {
            $p->setSubparts(app(\App\LDraw\PartManager::class)->parser->getSubparts($p->body->body));
        });
        PartEvent::create([
            'part_event_type_id' => \App\Models\Part\PartEventType::firstWhere('slug', 'delete')->id,
            'user_id' => $event->user->id,
            'deleted_filename' => $event->deleted_filename,
            'deleted_description' => $event->deleted_description,
        ]);
    }

    public function emailPartUpdateComplete(PartUpdateProcessingComplete $event): void
    {
        // Nothing yet
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            PartSubmitted::class => 'storeSubmitPartEvent',
            PartRenamed::class => 'storeRenamePartEvent',
            PartHeaderEdited::class => 'storePartHeaderEditEvent',
            PartReleased::class => 'storePartReleaseEvent',
            PartReviewed::class => 'storePartReviewEvent',
            PartComment::class => 'storePartCommentEvent',
            PartDeleted::class => 'storePartDeletedEvent',
            PartUpdateProcessingComplete::class => 'emailPartUpdateComplete',
        ];
    }
}
