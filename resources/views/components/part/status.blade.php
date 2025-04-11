@props(['part', 'showStatus' => false, 'showMyVote' => false])
@php
    $userVote = $part->votes->where('user_id', auth()->user()?->id)->first()?->vote_type
@endphp
<div>
    @if ($part->isUnofficial())
        @if (!$part->ready_for_admin)
            <x-library-icon icon="error" title="Not releaseable" class="inline w-7 fill-red-500" />
        @endif
        @if ($showMyVote)
            @empty($userVote)
                <x-library-icon icon="user-vote" class="inline w-7 fill-gray-400" title="My Vote: None"/>
            @else
                <x-library-icon :icon="$userVote->icon()" class="inline w-7 {{$userVote->iconColor()}}" title="My Vote: {{$userVote->label()}}" />
            @endempty
        @endif
        <x-library-icon :icon="$part->part_status->icon()" class="inline w-7 {{$part->part_status->iconColor()}}"
            title="{{$part->part_status->label()}} {{$part->statusCode()}}"
        />
            <span>{{$showStatus ? $part->part_status->label() : ''}} {{$part->statusCode()}}</span>
    @else
        <x-library-icon :icon="$part->part_status->icon()" class="inline w-7 {{$part->part_status->iconColor()}}" title="{{$part->part_status->label()}}" />
        <span>{{$part->part_status->label()}}</span>
    @endif
</div>
