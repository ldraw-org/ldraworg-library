@props(['header', 'open' => false])

<div x-data="{ open: @js($open) }" class="flex flex-col">

    <div {{ $header->attributes->class(['flex flex-row items-center gap-1']) }}>

        <x-library-icon
            icon="menu-right"
            class="w-7 cursor-pointer transition-transform"
            ::class="{ 'rotate-90': open }"
            @click="open = !open"
        />

        <div
            class="select-none cursor-pointer"
            @click="open = !open"
        >
            {{ $header }}
        </div>

    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
    >
        {{ $slot }}
    </div>

</div>
