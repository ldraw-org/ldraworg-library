<div class="flex flex-row space-x-2" {{ $getExtraAttributeBag() }}>
    <x-library-icon :icon="$getRecord()->vote_type->icon()" class="w-8 {{$getRecord()->vote_type->iconColor()}}" />
    <div>{{$getRecord()->vote_type->label()}}</div>
</div>
