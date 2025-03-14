<x-menu>
    <x-menu.item label="Library Main" link="{{route('index')}}" /> 
    <x-menu.item label="Parts List" link="{{route('parts.list')}}" /> 
    <x-menu.item label="Parts Tracker" link="{{route('tracker.main')}}" />
    <x-menu.item label="Part Updates" link="{{route('part-update.index')}}" />
    <x-menu.item label="Documentation" link="https://www.ldraw.org/docs-main.html" />
    <x-menu.item label="OMR" link="{{route('omr.main')}}" />
    <x-menu.dropdown label="Tools">
        <x-menu.item label="Part Search" link="{{route('parts.list')}}" />
        <x-menu.item label="Pattern/Shortcut Part Summary" link="{{route('parts.search.suffix')}}" /> 
        <x-menu.item label="Sticker Sheet Lookup" link="{{route('parts.sticker-sheet.index')}}" /> 
        <x-menu.item label="PBG Generator" link="{{route('pbg')}}" />
        <x-menu.item label="LDraw Model Viewer" link="{{route('model-viewer')}}" />
    </x-menu.dropdown>    
</x-menu>
