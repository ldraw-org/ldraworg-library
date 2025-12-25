<li class="relative list-none" 
    x-data="{ open: false }" 
    @mouseenter="if(window.innerWidth >= 768) open = true" 
    @mouseleave="if(window.innerWidth >= 768) open = false"
    @click.away="open = false">
    
    <div @class([
        'flex items-center justify-between w-full transition-colors cursor-pointer',
        'md:h-10 px-4' => $isTopLevel,
        'py-2 px-4 hover:bg-gray-100' => !$isTopLevel,
        'bg-blue-50 text-blue-700' => $isActive(), {{-- Method call to the class --}}
    ])>
        <a href="{{ $link }}" 
           @if($hasChildren) @click.prevent="open = !open" @endif
           class="flex-1 no-underline text-sm font-medium text-gray-700 whitespace-nowrap">
            {{ $label }}
        </a>

        @if($hasChildren)
            <x-library-icon icon="menu-down" 
                class="w-4 h-4 ml-2 transition-transform duration-200" 
                ::class="{
                    'rotate-180': open && {{ $isTopLevel ? 'true' : 'false' }},
                    'md:-rotate-90': !open && !{{ $isTopLevel ? 'true' : 'false' }},
                    'rotate-0': open && !{{ $isTopLevel ? 'true' : 'false' }}
                }" />
        @endif
    </div>

    @if($hasChildren)
        <div x-show="open" 
             x-cloak
             @class([
                'z-50 bg-white border border-gray-300 shadow-lg relative ml-4 md:ml-0',
                'md:absolute md:top-full md:left-0' => $isTopLevel,
                'md:absolute md:top-0 md:left-full' => !$isTopLevel,
             ])
             x-transition>
            
            <x-menu.index :items="$children" :depth="$depth + 1" />
        </div>
    @endif
</li>