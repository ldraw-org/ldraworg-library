<x-layout.tracker>
    <x-slot:title>
        Sticker Sheet {{$sheet->number}}
    </x-slot>
    <x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="Sticker Sheet" />
    </x-slot>
    <div>
        <div class="text-3xl font-bold">
            <span>Sticker Sheet {{$sheet->number}} - @if (!is_null($sheet->rebrickable)) <a class="underline decoration-dotted hover:decoration-solid" href="{{config('ldraw.external_sites.Rebrickable')}}\{{$sheet->rebrickable['part_num']}}">{{$sheet->rebrickable['name']}} ({{$sheet->rebrickable['part_num']}})</a> @endif </span>
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
