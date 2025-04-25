<x-menu> 
    <x-menu.library-menu-item />
    @can('create', \App\Models\Part\Part::class)
        <x-menu.top-level-item label="Submit" link="{{route('tracker.submit')}}" />
    @endcan
    <x-menu.top-level-item label="Parts List" link="{{route('parts.list')}}" />
    <x-menu.top-level-item label="Activity" link="{{route('tracker.activity')}}" />
    <x-menu.top-level-item label="Weekly New Parts" link="{{route('tracker.weekly')}}" />
    <x-menu.top-level-item label="Documentation" link="https://www.ldraw.org/docs-main.html" />
    <x-menu.top-level-item label="Tools">
        <x-menu.item label="Part Search" link="{{route('parts.list')}}" />
        <x-menu.item label="Pattern/Shortcut Part Summary" link="{{route('parts.search.suffix')}}" />
        <x-menu.item label="Sticker Sheet Lookup" link="{{route('parts.sticker-sheet.index')}}" />
        @can('create', \App\Models\Part\Part::class)
            <x-menu.item label="Torso Shortcut Helper" link="{{route('tracker.torso-helper')}}" />
        @endcan
        @if(!empty($summaries))
            <x-menu.submenu label="Review Summaries">
                @foreach($summaries as $summary)
                    <x-menu.submenu.item label="{{$summary->header}}" link="{{route('tracker.summary.view', $summary)}}" />
                @endforeach
            </x-menu.item>
        @endif
        <x-menu.item label="Download All Unofficial Files" link="{{asset('library/unofficial/ldrawunf.zip')}}" />
        <x-menu.item label="Download Last 24 Hours of Submits" link="{{route('tracker.last-day')}}" />
        <x-menu.item label="Parts in Next Update" link="{{route('tracker.next-release')}}" />
        <x-menu.item label="Parts Tracker History" link="{{route('tracker.history')}}" />
    </x-menu.item>
</x-menu>
