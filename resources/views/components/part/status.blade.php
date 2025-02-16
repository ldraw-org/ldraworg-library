@props(['part', 'showStatus' => false, 'showMyVote' => false])
@php
    $userVote = $part->votes->where('user_id', auth()->user()?->id)->first()?->vote_type
@endphp
<div class="flex flex-row space-x-2 align-contents-center">
    @if ($part->isUnofficial())
        @if (!$part->ready_for_admin)
            <x-fas-exclamation-triangle title="Not releaseable" class="inline w-5 text-yellow-800" />
        @endif
        @if ($showMyVote)
            <x-fas-user-circle @class([
                'inline w-5',
                'fill-gray-400' => is_null($userVote),
                'fill-lime-400' => $userVote == \App\Enums\VoteType::AdminCertify || $userVote == \App\Enums\VoteType::AdminFastTrack,
                'fill-green-400' => $userVote == \App\Enums\VoteType::Certify,
                'fill-red-500' => $userVote == \App\Enums\VoteType::Hold,

            ])
                title="My Vote: {{is_null($userVote) ? 'None' : $userVote->value}}"
            />
        @endif
        <x-fas-square @class([
            'inline w-5',
            'fill-lime-400' => $part->vote_sort == 1,
            'fill-blue-700' => $part->vote_sort == 2,
            'fill-gray-400' => $part->vote_sort == 3,
            'fill-red-500' => $part->vote_sort == 5,

        ])
            title="{{$part->statusText()}} {{$part->statusCode()}}"
        />
            <span>{{$showStatus ? $part->statusText() : ''}} {{$part->statusCode()}}</span>
    @else
        <x-fas-award title="Official" class="inline w-5 text-blue-800" />
        <span>Official Part</span>
    @endif
</div>
