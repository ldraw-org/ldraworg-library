<div>
    <x-filament::modal id="site-search" alignment="center" width="7xl" lazy>
        <x-slot name="trigger">
            <x-mdi-magnify class="w-8 h-8" />
        </x-slot>
        <x-slot name="heading">
            Search
        </x-slot>
        <div class="h-[40vh]">
            <form action="{{ route('parts.list') }}" method="get">
                <x-filament::input.wrapper>
                    <x-filament::input id="tableSearch" name="tableSearch" type="text" wire:input="doSearch" wire:model.live="tableSearch" />
                </x-filament::input.wrapper>
            </form>
            <div class="h-7/8 overflow-y-scroll p-2">
                @forelse($results as $lib => $parts)
                    <div class="flex flex-row" wire:key="lib-{{$loop->index}}">
                        <div class="bg-gray-200 font-bold text-gray-500 p-2 w-1/3 h-full">
                            {{$lib}}
                        </div>
                        <div class="flex flex-col divide-y w-2/3">
                            @foreach($parts as $id => $part)
                                <div class="py-2 pl-2 pr-4 hover:bg-gray-100" wire:key="part-{{$id}}">
                                    <a href="{{route($lib == 'OMR Models' ? 'omr.sets.show' : 'parts.show', $id)}}">
                                        <p class="text-sm font-bold">{{$part['name']}}</p>
                                        <p class="text-sm text-gray-500">{{$part['description']}}</p>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    {{--  --}}
                @endforelse
            </div>
        </div>
    </x-filament::modal>
</div>
