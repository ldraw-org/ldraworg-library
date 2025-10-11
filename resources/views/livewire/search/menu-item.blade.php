<div class="w-full relative">
    <form class="grid grid-col-1 h-full" id="pt_search_comp" action="{{route('parts.list', ['tableSearch' => $search])}}" method="get" name="pt_search_comp">
        <input class="border-gray-200 border-t border-b-0 border-r-0 py-2 md:py-0 pl-2 md:border-t-0 md:border-l w-full h-full lg:w-fit lg:justify-self-end" name="tableSearch" type="text" wire:model.live="search" wire:input="doSearch" placeholder="Quick Search">
        <div 
            class="flex flex-col border border-gray-200 rounded bg-white absolute top-full right-0 w-96 h-72 overflow-scroll z-50 divide-y"
            x-show="$wire.hasResults"
            x-transition:enter="transition ease-out duration-100" 
            x-transition:enter-start="transform opacity-0"
            x-cloak
        >
            @if($hasResults)
                @foreach($results as $lib => $parts)
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
                @endforeach
            @endif
        </div>
    </form>
</div>
