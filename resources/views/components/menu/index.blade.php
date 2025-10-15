<div x-data="{menuOpen: false}" >
    <x-mdi-menu class="w-8 h-8 md:hidden" @click.prevent="menuOpen = !menuOpen" />
    <nav :class="{hidden: !menuOpen, flex: menuOpen}" x-transistion class="hidden md:flex flex-row bg-white rounded-lg border border-gray-300 md:w-fit" >
        <ul class="flex flex-col divide-y divide-gray-200 items-start md:divide-y-0 md:divide-x md:flex-row lg:shrink-0">
            {{$slot}}
        </ul>
    </nav>
</div>