<div @class([
    'flex',
    'flex-row space-x-4 place-content-center' => $small,
    'flex-col space-y-2' => !$small,
    'w-fit'
])>
    @foreach (\App\Enums\PartStatus::trackerStatus() as $status)
        <div @class([
            'flex',
            'flex-col place-items-center' => $small,
            'flex-row space-x-2 items-center justify-items-start' => !$small
        ])>
            <x-dynamic-component :component="$status->icon()" class="inline w-5 {{$status->iconColor()}}" title="{{$status->label()}}" />
            <div>{{$summary[$status->value]}}{{$small ? '' : ' ' . $status->label()}}</div>
        </div>
    @endforeach
</div>
