<x-layout.base>
    <x-slot:title>LDraw.org Library Updates</x-slot>
    <x-slot:menu>
        <x-menu.library />
    </x-slot>
    <div class="flex flex-col space-y-2">
        @if($latest)
            <x-release.table :release="$releases"/>   
        @else  
            @foreach($releases as $release)
                @if ($loop->first)
                    <x-release.table :release="$release"/>   
                @else
                    <x-release.table :release="$release" current="0"/>   
                @endif
            @endforeach
        @endif  
    </div>
</x-layout.base>