<x-card
    {{ $attributes }}
    title="Parts Update: {{$update->name}}"
    link="{{route('part-update.index', ['latest'])}}"
    image="{{$update->getFirstMediaUrl('image','card')}}"
>
    {{$update->blurb}}. As of this update, the LDraw.org Parts Library contains {{ $officialCount }} unique shapes or patterned parts.
</x-card>
