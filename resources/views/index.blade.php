<x-layout.base>
    <x-slot:title>
        LDraw.org Library Main
    </x-slot>
    <x-slot:menu>
        <x-menu.library />
    </x-slot>
    <div class="flex flex-col space-y-2 w-full place-items-center">
        <div class="bg-white border border-gray-200 rounded-lg p-2">
            Welcome to the LDraw.org library. Here you will find the Parts Tracker, parts updates, 
            documentation for the LDraw file format and library, and the Official Model Repository
        </div>
        
        <div class="flex flex-row space-x-2">
            <x-card class="max-w-150 bg-white" image="{{asset('/images/cards/tracker.png')}}" link="{{route('tracker.main')}}">
                <x-slot:title>Parts Tracker</x-slot>
                The Parts Tracker is the system we use to submit files to the LDraw.org Part Library.
                The Parts Tracker allows users to download unofficial parts, submit new files, update existing unofficial files, and review unofficial parts.
            </x-card>  
            @if(!is_null(\App\Models\Part\PartRelease::current()))
                <x-card.latest-update class="max-w-150 bg-white" /> 
            @endif
        </div>
        <div class="flex flex-row space-x-2">
            <x-card class="max-w-150 bg-white" image="{{asset('/images/cards/doc.png')}}" link="https://www.ldraw.org/docs-main.html">
                <x-slot:title>Documentation</x-slot>
                The reference docmentation for the LDraw File Format and LDraw.org Official Parts Library.
            </x-card>  
            <x-card class="max-w-150 bg-white" image="{{asset('/images/cards/omr.png')}}" link="{{route('omr.main')}}">
                <x-slot:title>Official Model Repository</x-slot>
                The Official Model Repository or OMR is a library of official LEGO&reg; sets that have been
                created in LDRaw format.
            </x-card>  
        </div>
    </div>
</x-layout.base>
