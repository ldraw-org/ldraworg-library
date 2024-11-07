<x-menu>
    <x-menu.library-dropdown />
    @can('create', \App\Models\Part::class)
        <x-menu.item label="Submit" link="{{route('tracker.submit')}}" />
    @endcan
    <x-menu.item label="Parts List" link="{{route('parts.list')}}" /> 
    <x-menu.item label="Activity" link="{{route('tracker.activity')}}" /> 
    <x-menu.item label="Weekly New Parts" link="{{route('tracker.weekly')}}" />
    <x-menu.item label="Documentation" link="https://www.ldraw.org/docs-main.html" />
    <x-menu.dropdown label="Tools">
        <x-menu.item label="Part Search" link="{{route('parts.list')}}" />
        <x-menu.item label="Pattern/Shortcut Part Summary" link="{{route('parts.search.suffix')}}" /> 
        <x-menu.item label="Sticker Sheet Lookup" link="{{route('parts.sticker-sheet.index')}}" /> 
        @if(!empty($summaries))
            <x-menu.dropdown label="Review Summaries" level="1">
                @foreach($summaries as $summary)
                    <x-menu.item label="{{$summary->header}}" link="{{route('tracker.summary.view', $summary)}}" /> 
                @endforeach
            </x-menu.dropdown>
        @endif
        <x-menu.item label="Download All Unofficial Files" link="{{asset('library/unofficial/ldrawunf.zip')}}" />
        <x-menu.item label="Download Last 24 Hours of Submits" link="{{route('tracker.last-day')}}" />
        <x-menu.item label="Parts in Next Update" link="{{route('tracker.next-release')}}" />
        <x-menu.item label="Parts Tracker History" link="{{route('tracker.history')}}" />
    </x-menu.dropdown>
</x-menu>
