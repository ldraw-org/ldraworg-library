<x-menu>
    <x-menu.top-level-item label="LDraw.org" link="https://www.ldraw.org" />
    <x-menu.top-level-item label="Library" link="{{route('index')}}" /> 
    <x-menu.top-level-item label="Parts" link="{{route('parts.list')}}" /> 
    <x-menu.top-level-item label="Parts Tracker" link="{{route('tracker.main')}}" />
    <x-menu.top-level-item label="Part Updates" link="{{route('part-update.index')}}" />
    <x-menu.top-level-item label="Documentation" link="{{route('documentation.index')}}" />
    <x-menu.top-level-item label="OMR" link="{{route('omr.main')}}" />
    <x-menu.top-level-item label="Tools">
        <x-menu.item label="Part Search" link="{{route('parts.list')}}" />
        <x-menu.item label="Pattern Shortcut Part Summary" link="{{route('parts.search.suffix')}}" /> 
        <x-menu.item label="Sticker Sheet Lookup" link="{{route('parts.sticker-sheet.index')}}" /> 
        <x-menu.item label="PBG Generator" link="{{route('pbg')}}" />
        <x-menu.item label="LDraw Model Viewer" link="{{route('model-viewer')}}" />
        <x-menu.item label="User List" link="{{route('users.index')}}" />
    </x-menu.top-level-item>    
</x-menu>
