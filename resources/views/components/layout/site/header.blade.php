@props(['logo', 'menu'])
<header class="w-full py-4 px-2">
    <div class="flex flex-wrap items-center w-full">

        {{-- Logo --}}
        <div class="flex-none order-1">
            <a href="{{ route('index') }}" class="block">
                <img
                    src="{{ asset('/images/banners/' . $logo . '.png') }}"
                    class="w-auto h-auto max-h-12 md:max-h-14"
                    alt="{{ config('app.name') }} logo"
                >
            </a>
        </div>

        {{-- Right icons --}}
        <div class="flex items-center space-x-3 shrink-0 order-3 ml-auto">
            @livewire('search.menu-item')
            @auth
                <x-menu.user-icon />
            @else
                <div class="border rounded-lg px-2 py-1 bg-white">
                    <a href="{{ route('login') }}">Login</a>
                </div>
            @endauth
        </div>

        {{-- Menu --}}
        <nav
            class="
                order-4
                basis-full
                mt-4
                flex
                justify-start

                xl:order-2
                xl:basis-auto
                xl:mt-0
                xl:justify-center
                xl:px-4
            "
            aria-label="Primary navigation"
        >
            <x-menu :type="$menu" />
        </nav>

    </div>
</header>

