<x-menu.top-level-item label="Library">
    <x-menu.item label="LDraw.org Main Site" link="https://www.ldraw.org" />
    <x-menu.item label="Library Main" link="{{route('index')}}" />
    <x-menu.item label="Parts List" link="{{route('parts.list')}}" /> 
    <x-menu.item label="Parts Tracker" link="{{route('tracker.main')}}" />
    <x-menu.item label="Latest Update" link="{{route('part-update.index', ['latest'])}}" />
    <x-menu.item label="Update Archive" link="{{route('part-update.index')}}" />
    <x-menu.item label="OMR" link="{{route('omr.main')}}" />
    <x-menu.submenu label="Tools">
        <x-menu.submenu.item label="Part Search" link="{{route('parts.list')}}" />
        <x-menu.submenu.item label="Pattern/Shortcut Part Summary" link="{{route('parts.search.suffix')}}" /> 
        <x-menu.submenu.item label="Sticker Sheet Lookup" link="{{route('parts.sticker-sheet.index')}}" /> 
        <x-menu.submenu.item label="PBG Generator" link="{{route('pbg')}}" />
        <x-menu.submenu.item label="LDraw Model Viewer" link="{{route('model-viewer')}}" />
        <x-menu.submenu.item label="User List" link="{{route('users.index')}}" />
    </x-menu.item>  
</x-menu.item>
