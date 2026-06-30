<?php

namespace App\View\Components\Event;

use Closure;
use App\Enums\EventType;
use App\Enums\LibraryIcon;
use App\Enums\VoteType;
use App\Models\Part\PartEvent;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Icon extends Component
{
    public readonly LibraryIcon $icon;
    public readonly ?LibraryIcon $lowerLeftIcon;
    public readonly ?LibraryIcon $lowerRightIcon;
    public readonly string $iconColor;
    public readonly ?string $lowerLeftIconColor;
    public readonly ?string $lowerRightIconColor;

    public function __construct(
        protected PartEvent $event
    ) {
        [$this->icon, $this->iconColor] = $this->getIconAndColor();
        [$this->lowerLeftIcon, $this->lowerLeftIconColor] = $this->getLowerLeftIconAndColor();
        [$this->lowerRightIcon, $this->lowerRightIconColor] = $this->getLowerRightIconAndColor();
    }

    /**
     * @return array{0: LibraryIcon, 1: string}
     */
    protected function getIconAndColor(): array
    {
        if ($this->event->event_type !== EventType::Review) {
            return [$this->event->event_type->icon(), $this->event->event_type->iconColor()];
        }

        $voteType = $this->event->vote_type ?? VoteType::CancelVote;

        return [$voteType->icon(), $voteType->iconColor()];
    }

    /**
     * @return array{0: ?LibraryIcon, 1: ?string}
     */
    private function getLowerLeftIconAndColor(): array
    {
        if ($this->event->comment === null) {
            return [null, null];
        }

        return match($this->event->event_type) {
            EventType::Review,
            EventType::Submit,
            EventType::HeaderEdit,
            EventType::Rename => [LibraryIcon::Comment, VoteType::Comment->iconColor()],
            default => [null, null]
        };
    }

    /**
     * @return array{0: ?LibraryIcon, 1: ?string}
     */
    private function getLowerRightIconAndColor(): array
    {
        if ($this->event->event_type !== EventType::Submit) {
            return [null, null];
        }

        if ($this->event->initial_submit && $this->event->part->official_part !== null) {
            return [LibraryIcon::PartFix, 'fill-green-400'];
        }

        return [null, null];
    }

    public function render(): View|Closure|string
    {
        return view('components.event.icon.index');
    }

}
