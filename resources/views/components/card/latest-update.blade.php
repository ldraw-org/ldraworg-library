<x-card 
    title="Parts Update {{$update->name}}"
    link="{{route('part-update.index', ['latest'])}}"
    image="{{asset('/images/cards/updates.png')}}"
>
    {{$update->blurb}}
</x-card>