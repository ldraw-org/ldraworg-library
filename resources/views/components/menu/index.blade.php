@if($depth === 0)
    <div x-data="{menuOpen: false}" class="relative">
        <x-mdi-menu class="w-8 h-8 md:hidden" @click.prevent="menuOpen = !menuOpen" />
        <nav :class="{hidden: !menuOpen, flex: menuOpen}" 
             class="hidden md:flex flex-row bg-white rounded-lg border border-gray-300 md:w-fit">
            <ul class="flex flex-col divide-y md:flex-row items-stretch md:divide-x divide-gray-200">
                @foreach($items as $item)
                    <x-menu.item :item="$item" :depth="$depth" />
                @endforeach
            </ul>
        </nav>
    </div>
@else
    <ul class="flex flex-col min-w-[200px] py-1">
        @foreach($items as $item)
            @empty($item)
              @dd($items, $item)
            @endempty
            <x-menu.item :item="$item" :depth="$depth" />
        @endforeach
    </ul>
@endif