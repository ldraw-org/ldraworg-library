<x-menu>
    <x-menu.library-menu-item />
    @can('create', App\Models\Omr\OmrModel::class)
        <x-menu.top-level-item label="Review" link="{{route('omr.add')}}" />
    @endcan
    <x-menu.top-level-item label="Model List" link="{{route('omr.sets.index')}}" />
    <x-menu.top-level-item label="Statistics" link="" />
    <x-menu.top-level-item label="Documentation" link="{{route('documentation.index')}}" />
</x-menu>