<x-menu>
    <x-menu.library-dropdown />
    @can('create', App\Models\Omr\OmrModel::class)
        <x-menu.item label="Review" link="{{route('omr.add')}}" />
    @endcan
    <x-menu.item label="Model List" link="{{route('omr.sets.index')}}" />
    <x-menu.item label="Statistics" link="" />
    <x-menu.dropdown label="Documentation">
        <x-menu.item label="Official Model Repository (OMR) Specification" link="https://www.ldraw.org/article/593.html" />
        <x-menu.item label="Rules and procedures for the Official Model Repository" link="https://www.ldraw.org/docs-main/official-model-repository-omr/rules-and-procedures-for-the-official-model-repository.html" />
    </x-menu.dropdown>
</x-menu>