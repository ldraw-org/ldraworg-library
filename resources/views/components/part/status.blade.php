@props(['part', 'showStatus' => false, 'showMyVote' => false])
@php
    $userVote = $part->votes->where('user_id', auth()->user()?->id)->first()?->vote_type
@endphp
<div>
    @if ($part->isUnofficial())
        @if (!$part->ready_for_admin)
            <x-fas-exclamation-triangle title="Not releaseable" class="inline w-5 fill-yellow-800" />
        @endif
        @if ($showMyVote)
            @empty($userVote)
                <x-fas-user-circle class="inline w-5 fill-gray-400" title="My Vote: None"/>
            @else
                <x-dynamic-component :component="$userVote->icon()" class="inline w-5 {{$userVote->iconColor()}}" title="My Vote: {{$userVote->label()}}" />
            @endempty
        @endif
        <x-fas-square class="inline w-5 {{$part->part_status->iconColor()}}"
            title="{{$part->part_status->label()}} {{$part->statusCode()}}"
        />
            <span>{{$showStatus ? $part->part_status->label() : ''}} {{$part->statusCode()}}</span>
    @else
        <x-dynamic-component :component="$part->part_status->icon()" class="inline w-5 {{$part->part_status->iconColor()}}" title="{{$part->part_status->label()}}" />
        <span>{{$part->part_status->label()}}</span>
    @endif
</div>
