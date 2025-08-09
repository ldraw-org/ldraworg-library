@props(['label'])
<li class="relative" x-data="{showChildren:false}" @mouseleave="showChildren=false" @mouseenter="showChildren=true">
    <a href="#" class="px-4 py-2 flex w-full items-start hover:bg-gray-100 no-underline hover:no-underline transition-colors duration-100 cursor-pointer">
        <span class="flex-1">{{ $label }}</span>
        <span class="ml-1"> <x-library-icon icon="menu-right" class="w-6" /> </span>
    </a>
    <div class="bg-white shadow-md rounded border border-gray-300 text-sm absolute inset-l-full top-0 min-w-full w-56 z-30 mt-1" x-show="showChildren" x-transition:enter="transition ease duration-300 transform" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease duration-300 transform" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4" style="display: none;">
        <span class="absolute top-0 left-0 w-3 h-3 bg-white border border-gray-200 transform rotate-45 -ml-1 mt-2"></span>
        <div class="bg-white rounded w-full relative z-10 py-1">
            <ul class="list-reset">
                {{  $slot }}
            </ul>
        </div>
    </div>
</li>