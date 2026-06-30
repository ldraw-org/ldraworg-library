<?php

namespace App\View\Components\Event;

use App\Enums\EventType;
use App\Enums\LibraryIcon;
use App\Models\Part\PartEvent;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ListItem extends Component
{
    public readonly string $authorString;
    public readonly ?LibraryIcon $authorIcon;
    public readonly ?string $authorIconTitle;
    public readonly ?string $comment;
    public readonly string $eventText;
    public readonly bool $hasHeaderEditAccordion;
    public readonly string $formattedDate;


    public function __construct(
        public PartEvent $event
    ) {
        $this->authorString = $event->user->author_string;
        [$this->authorIcon, $this->authorIconTitle] = $this->getAuthorIconAndTitle();
        $this->comment = $event->event_type == EventType::HeaderEdit ?
            "\"{$this->event->moved_from_filename}\" to \"{$this->event->moved_to_filename}\"" :
            $event->processedComment();
        $this->eventText = $this->getEventText();
        $this->hasHeaderEditAccordion = $event->event_type == EventType::HeaderEdit && !is_null($event->header_changes);
        $this->formattedDate = $this->event->created_at->setTimezone(Auth::user()?->timezone ?? 'UTC')->format('Y-m-d h:i:s');
    }
    public function render(): View|Closure|string
    {
        return view('components.event.list.item');
    }

    private function getAuthorIconAndTitle(): array
    {
        if ($this->event->user->hasRole('Library Admin')) {
            return [LibraryIcon::UserLibraryAdmin, 'Part Library Admin'];
        }
        if ($this->event->user->hasRole('Senior Reviewer')) {
            return [LibraryIcon::UserSeniorReviewer, 'Senior Part Reviewer'];
        }
        if ($this->event->user->hasRole('Part Header Editor')) {
            return [LibraryIcon::UserSeniorReviewer, 'Part Header Editor'];
        }
        if ($this->event->user->hasRole('Part Reviewer')) {
            return [LibraryIcon::UserPartReviewer, 'Part Reviewer'];
        }
        if ($this->event->user->hasRole('Part Author')) {
            return [LibraryIcon::UserPartAuthor, 'Part Author'];
        }
        return [null, null];
    }

    private function getEventText(): string
    {
        if ($this->event->event_type === EventType::Submit) {
            return $this->event->initial_submit === true ?
                'initially submitted the part.' :
                'submitted a new version of the part (all votes reset).';
        }
        if ($this->event->event_type === EventType::HeaderEdit) {
            return 'edited the part header.';
        }
        if ($this->event->event_type === EventType::Review) {
            return $this->event->vote_type === null ?
                'cancelled their vote.' :
                "posted a vote of {$this->event->vote_type->label()}";
        }
        if ($this->event->event_type === EventType::Comment) {
            return 'made the following comment.';
        }
        if ($this->event->event_type === EventType::Rename) {
            return 'renamed the part.';
        }
        return '';
    }
}
