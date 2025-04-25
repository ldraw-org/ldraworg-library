<x-menu>
    <x-menu.library-menu-item />
    @can('create', App\Models\Omr\OmrModel::class)
        <x-menu.top-level-item label="Review" link="{{route('omr.add')}}" />
    @endcan
    <x-menu.top-level-item label="Model List" link="{{route('omr.sets.index')}}" />
    <x-menu.top-level-item label="Statistics" link="" />
    <x-menu.top-level-item label="Documentation">
        <x-menu.item label="Official Model Repository (OMR) Specification" link="https://www.ldraw.org/article/593.html" />
        <x-menu.item label="Rules and procedures for the Official Model Repository" link="https://www.ldraw.org/docs-main/official-model-repository-omr/rules-and-procedures-for-the-official-model-repository.html" />
    </x-menu.top-level-item>
</x-menu>