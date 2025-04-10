@props(['event'])
@php
    $is_fix = $event->initial_submit && !is_null($event->part->official_part);
    $stack = (!is_null($event->comment) && $event->event_type != \App\Enums\EventType::Comment) || $is_fix;
@endphp
<div class="relative w-8">
@if($event->event_type == \App\Enums\EventType::Review)
    @empty($event->vote_type)
        <x-dynamic-component :component="\App\Enums\VoteType::CancelVote->icon()" class="{{\App\Enums\VoteType::CancelVote->iconColor()}}" />
    @else
        <x-dynamic-component :component="$event->vote_type->icon()" class="{{$event->vote_type->iconColor()}}" />
    @endempty
@else
    <x-dynamic-component :component="$event->event_type->icon()" class="{{$event->event_type->iconColor()}}" />
@endif
@if($stack)
    @if(!is_null($event->comment))
        <x-dynamic-component :component="\App\Enums\EventType::Comment->icon()" class="absolute bottom-0 left-0 w-1/3 {{\App\Enums\EventType::Comment->iconColor()}}" />
    @endif
    @if($is_fix)
        <x-mdi-tools class="absolute bottom-0 right-0 w-1/3 fill-green-400" />
    @endif
@endif
</div>
