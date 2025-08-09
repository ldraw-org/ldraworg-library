@props(['label', 'link' => ''])

<li class="block relative w-full md:w-fit h-full"
    @if ($slot->hasActualContent())
        x-data="{showChildren:false}"
        @click.away="showChildren=false"
    @endif
>
    <a href="{{ $link }}" class="flex items-center h-full leading-10 rounded cursor-pointer no-underline hover:no-underline transition-colors duration-100 px-2 hover:bg-gray-100"
        @if ($slot->hasActualContent())
            @click.prevent="showChildren=!showChildren"
        @endif
    >
        <span>{{ $label }}</span>
        @if ($slot->hasActualContent())
            <span class="ml-1">
                <x-library-icon icon="menu-down" class="w-6" />
            </span>
        @endif            
    </a>
    @if ($slot->hasActualContent())
        <div class="bg-white shadow-md rounded border border-gray-300 text-sm absolute top-auto left-0 min-w-full w-56 z-30 mt-1" x-show="showChildren" x-transition:enter="transition ease duration-300 transform" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease duration-300 transform" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4" style="display: none;">
            <span class="absolute top-0 left-0 w-3 h-3 bg-white border border-gray-200 transform rotate-45 -mt-1 ml-6"></span>
            <div class="bg-white rounded w-full relative z-10 py-1">
                <ul class="list-reset">
                    {{  $slot }}
                </ul>
            </div>
        </div>
    @endif
</li>