<div class="flex flex-row space-x-2">
    <x-dynamic-component :component="$getRecord()->vote_type->icon()" class="w-6 {{$getRecord()->vote_type->iconColor()}}" />
    <div>{{$getRecord()->vote_type->label()}}</div>
</div>
