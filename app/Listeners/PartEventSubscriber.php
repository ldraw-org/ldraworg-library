<?php

namespace App\Listeners;

use App\Enums\EventType;
use App\Events\PartComment;
use App\Events\PartDeleted;
use App\Events\PartHeaderEdited;
use App\Events\PartReleased;
use App\Events\PartRenamed;
use App\Events\PartReviewed;
use App\Events\PartSubmitted;
use App\Events\PartUpdateProcessingComplete;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use Illuminate\Events\Dispatcher;

class PartEventSubscriber
{
    public function storeSubmitPartEvent(PartSubmitted $event): void
    {
        $init_submit = is_null(PartEvent::unofficial()->firstWhere('part_id', $event->part->id));
        PartEvent::create([
            'event_type' => EventType::Submit,
            'initial_submit' => $init_submit,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'comment' => $event->comment,
        ]);
    }

    public function storeRenamePartEvent(PartRenamed $event): void
    {
        PartEvent::create([
            'event_type' => EventType::Rename,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'moved_to_filename' => $event->moved_to,
            'moved_from_filename' => $event->moved_from,
        ]);
    }

    public function storePartHeaderEditEvent(PartHeaderEdited $event): void
    {
        PartEvent::create([
            'event_type' => EventType::HeaderEdit,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'header_changes' => $event->changes,
            'comment' => $event->comment,
        ]);
    }

    public function storePartReleaseEvent(PartReleased $event): void
    {
        PartEvent::create([
            'event_type' => EventType::Release,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'part_release_id' => $event->release->id,
            'comment' => "Release {$event->release->name}",
        ]);
    }

    public function storePartReviewEvent(PartReviewed $event): void
    {
        PartEvent::create([
            'event_type' => EventType::Review,
            'part_evnt_type_id' => 1,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'vote_type' => $event->vote_type,
            'comment' => $event->comment,
        ]);
    }

    public function storePartCommentEvent(PartComment $event): void
    {
        PartEvent::create([
            'event_type' => EventType::Comment,
            'user_id' => $event->user->id,
            'part_id' => $event->part->id,
            'comment' => $event->comment,
        ]);
    }

    public function storePartDeletedEvent(PartDeleted $event): void
    {
        Part::whereIn('id', $event->parentIds)->each(function (Part $p) {
            $p->setSubparts(app(PartManager::class)->parser->getSubparts($p->body->body));
        });
        PartEvent::create([
            'event_type' => EventType::Delete,
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
