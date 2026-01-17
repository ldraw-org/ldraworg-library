<div>
    <x-filament::modal id="site-search" alignment="center" width="7xl" lazy>
        <x-slot name="trigger">
            <x-mdi-magnify class="w-8 h-8" />
        </x-slot>

        <x-slot name="heading">
            Search Library
        </x-slot>

        <div class="h-[60vh] flex flex-col gap-4">
            <form wire:submit="submitToTable">
                <x-filament::input.wrapper>
                    <x-filament::input 
                        id="tableSearch" 
                        name="tableSearch" 
                        type="text" 
                        wire:model.live.debounce.300ms="tableSearch" 
                        placeholder="Search parts, sets, or themes..."
                        autocomplete="off"
                        autofocus
                    />
                    <x-slot name="suffix">
                        <x-filament::loading-indicator wire:loading wire:target="tableSearch" class="h-5 w-5" />
                    </x-slot>
                </x-filament::input.wrapper>
            </form>

            <div class="flex-1 overflow-y-auto space-y-6 p-1">
                @forelse($this->results as $lib => $items)
                    <div wire:key="group-{{ str($lib)->slug() }}">
                        <div class="flex items-center gap-2 px-2 mb-2">
                            <span class="text-xs font-bold tracking-widest uppercase text-gray-400">
                                {{ $lib }}
                            </span>
                            <div class="h-px flex-1 bg-gray-100"></div>
                        </div>

                        <div class="grid grid-cols-1 gap-1">
                            @foreach($items as $id => $item)
                                <a 
                                    href="{{ route($lib === 'OMR Models' ? 'omr.sets.show' : 'parts.show', $id) }}" 
                                    wire:key="item-{{ $id }}"
                                    class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                                >
                                    <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-inner bg-gray-100 group-hover:bg-white border border-transparent group-hover:border-gray-200 shadow-sm">
                                        @if(!empty($item['image_url']))
                                            <img 
                                                src="{{ $item['image_url'] }}" 
                                                alt="{{ $item['name'] }}" 
                                                class="w-full h-full object-contain p-1"
                                                loading="lazy"
                                            />
                                        @else
                                            {{-- Fallback icon if image is missing --}}
                                            <x-mdi-image-off-outline class="w-5 h-5 text-gray-300" />
                                        @endif 
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $item['name'] }}
                                            </p>
                                        </div>
                                        <p class="text-xs font-mono text-gray-500 truncate">
                                            {{ $item['description'] }}
                                        </p>
                                    </div>

                                    <x-mdi-chevron-right class="w-4 h-4 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    @if(strlen($tableSearch) > 0)
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <x-mdi-database-search class="w-12 h-12 mb-2 opacity-20" />
                            <p>No matches found for "{{ $tableSearch }}"</p>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>
    </x-filament::modal>
</div>