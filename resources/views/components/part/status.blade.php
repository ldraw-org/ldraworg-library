@props(['part', 'showStatus' => false])
<div>
    @if ($part->isUnofficial())
        @if (!$part->ready_for_admin)
            <x-fas-exclamation-triangle title="Not releaseable" class="inline w-5 text-yellow-800" />
        @endif
        <x-fas-square @class([
            'inline w-5',
            'fill-lime-400' => $part->vote_sort == 1,
            'fill-blue-700' => $part->vote_sort == 2,
            'fill-gray-400' => $part->vote_sort == 3,
            'fill-red-600' => $part->vote_sort == 5,

        ]) />
        <span>{{$showStatus ? $part->statusText() : ''}} {{$part->statusCode()}}</span>
    @else
        <x-fas-award title="Official" class="inline w-5 text-blue-800" />
        <span>Official Part</span>
    @endif
</div>