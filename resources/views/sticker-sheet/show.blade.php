<x-layout.tracker>
    <x-slot:title>
        Sticker Sheet {{$sheet->number}}
    </x-slot>
    <div>
        <div class="text-3xl font-bold">
            <span>
                @if( $sheet->is_local)
                    {{$sheet->number}} - {{$sheet->name}}
                @else
                    Sticker Sheet - <a class="underline decoration-dotted hover:decoration-solid" href="{{$sheet->url}}">{{$sheet->name}} ({{$sheet->number}})</a>
                @endif
            </span>
        </div>
        <div class="space-y-2">
            @if (!is_null($sheet->parts))
                <div class="rounded text-xl font-bold bg-gray-200 p-2">Flat Stickers</div>
                <x-part.grid :parts="$flat" />
                <div class="rounded text-xl font-bold bg-gray-200 p-2">Formed Stickers</div>
                <x-part.grid :parts="$formed" />
                <div class="rounded text-xl font-bold bg-gray-200 p-2">Shortcuts</div>
                <x-part.grid :parts="$shortcuts" />
            @endif
        </div>
    </div>
</x-layout.tracker>
