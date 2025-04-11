@props(['event'])

@switch ($event->event_type)
    @case(\App\Enums\EventType::Review)
        @empty($event->vote_type)
            <x-library-icon 
                :icon="\App\Enums\VoteType::CancelVote->icon()"
                color="{{\App\Enums\VoteType::CancelVote->iconColor()}}"
                class="w-8"
                lowerLeftIcon="{{ !is_null($event->comment) ? 'comment' : null }}"
                lowerLeftColor="{{\App\Enums\VoteType::Comment->iconColor()}}"
            />
        @else
            <x-library-icon 
                :icon="$event->vote_type->icon()" 
                color="{{$event->vote_type->iconColor()}}"
                class="w-8" 
                lowerLeftIcon="{{ !is_null($event->comment) ? 'comment' : null }}"
                lowerLeftColor="{{\App\Enums\VoteType::Comment->iconColor()}}"
            />
        @endempty
        @break
    @case(\App\Enums\EventType::Submit)
        <x-library-icon 
            :icon="$event->event_type->icon()"
            color="{{$event->event_type->iconColor()}}"
            class="w-8" 
            lowerLeftIcon="{{ !is_null($event->comment) ? 'comment' : null }}"
            lowerLeftColor="{{\App\Enums\VoteType::Comment->iconColor()}}"
            lowerRightIcon="{{ $event->initial_submit && !is_null($event->part->official_part) ? 'part-fix' : null }}"
            lowerRightColor="fill-green-400"
        />
        @break
    @case(\App\Enums\EventType::Comment)
    @case(\App\Enums\EventType::Release)
        <x-library-icon :icon="$event->event_type->icon()" color="{{$event->event_type->iconColor()}}" class="w-8" />
        @break
    @default
        <x-library-icon 
            :icon="$event->event_type->icon()" 
            color="{{$event->event_type->iconColor()}}"
            class="w-8"
            lowerLeftIcon="{{ !is_null($event->comment) ? 'comment' : null }}"
            lowerLeftColor="{{\App\Enums\VoteType::Comment->iconColor()}}"
        />
@endswitch
