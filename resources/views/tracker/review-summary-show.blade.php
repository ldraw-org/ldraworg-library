<x-layout.tracker>
    <x-slot:title>{{$summary->header}}</x-slot>
    <div class="text-2xl font-bold">{{$summary->header}}</div>
    <div class="flex flex-col space-y-2">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 items-stretch p-2">
            @foreach($list as $item)
                @if(str($item)->trim()->startsWith('/'))
                    </div>
                    @if(trim($item) == '/')
                        <hr>
                    @else
                        <div class="text-lg font-bold">{{str($item)->trim()->replaceStart('/', '')->trim()}}</div>
                    @endif
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 items-stretch">
                @elseif(!is_null($parts->firstWhere('filename', trim($item))))   
                    <x-part.grid.item :part="$parts->firstWhere('filename', trim($item))" show_obsolete />
                @endif
            @endforeach
        </div>
    </div>            
</x-layout.tracker>