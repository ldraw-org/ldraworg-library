@props(['event'])
@php
    $is_fix = $event->initial_submit && !is_null($event->part->official_part);
    $stack = (!is_null($event->comment) && $event->event_type != \App\Enums\EventType::Comment) || $is_fix;
@endphp
<div class="relative w-6">
@switch($event->event_type)
    @case(\App\Enums\EventType::Submit)
        <x-fas-file class="fill-black" />
        @break
    @case(\App\Enums\EventType::Review)
        @switch($event->vote_type)
            @case(\App\Enums\VoteType::AdminCertify)
            @case(\App\Enums\VoteType::AdminFastTrack)
                <x-fas-check class="fill-lime-400" />
                @break
            @case(\App\Enums\VoteType::Certify)
                <x-fas-check class="fill-green-400" />
                @break
            @case(\App\Enums\VoteType::Hold)
                <x-fas-circle-exclamation class="fill-red-500" />
                @break
            @default
                <x-fas-undo class="fill-black" />
        @endswitch
        @break
    @case(\App\Enums\EventType::Comment)
        <x-fas-comment class="fill-blue-500" />
        @break
    @case(\App\Enums\EventType::HeaderEdit)
        <x-fas-edit class="fill-black" />
        @break
    @case(\App\Enums\EventType::Rename)
        <x-fas-file-export class="fill-black" />
        @break
    @case(\App\Enums\EventType::Release)
        <x-fas-graduation-cap class="fill-green-600" />
        @break
    @case(\App\Enums\EventType::Delete)
        <x-fas-recycle class="fill-black" />
        @break
    @default
        <x-fas-circle class="fill-blue-500" />
@endswitch
@if($stack)
    @if(!is_null($event->comment))
        <x-fas-comment class="absolute bottom-0 left-0 w-1/2 fill-blue-500" />
    @endif
    @if($is_fix)
        <x-fas-tools class="absolute bottom-0 right-0 w-1/2 fill-green-400" />
    @endif
@endif
</div>
