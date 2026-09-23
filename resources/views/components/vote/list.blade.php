@props(['votes'])
<div {{ $attributes }} >
    <h3 class="font-bold">Current Votes:</h3>
@forelse($votes as $vote)
    <div class="flex flex-row ps-2">
        <x-library-icon
            :icon="$vote->vote_type->icon()"
            class="w-6"
            color="{{$vote->vote_type->iconColor()}}"
            title="{{$vote->vote_type->label()}}"
        />
        <div class="ps-2">
            {{ $vote->user->author_string }}
        </div>
    </div>
@empty
    No recorded votes
@endforelse
</div>
