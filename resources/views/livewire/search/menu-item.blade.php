<div class="flex grow md:justify-end relative">
    <form class="border rounded" id="pt_search_comp" action="{{route('search.part')}}" method="get" name="pt_search_comp">
        <input class="border-none w-full md:w-fit" name="s" type="text" wire:model.live="search" wire:input="doSearch" placeholder="Quick Search">
        <div 
            class="flex flex-col border rounded bg-white absolute mt-2 right-0 w-96 h-72 overflow-scroll z-50 divide-y divide-black"
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
                                    <a href="{{route($lib == 'OMR Models' ? 'omr.sets.show' : 'tracker.show', $id)}}">
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