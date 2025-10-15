<x-menu>
    <x-menu.library-menu-item />
    @if(
        Auth::user()->can('manage', \App\Models\Part\PartKeyword::class) ||
        Auth::user()->can(\App\Enums\Permission::SiteSettingsEdit) ||
        Auth::user()->can(\App\Enums\Permission::LdconfigEdit)
    )
        <x-menu.top-level-item label="Library">
            @can(\App\Enums\Permission::SiteSettingsEdit)
            <x-menu.item label="General Library Settings" link="{{route('admin.settings.index')}}" />
            @endcan
            @can(\App\Enums\Permission::LdconfigEdit)
                <x-menu.item label="Edit LDConfig" link="{{route('admin.ldconfig.index')}}" />
            @endcan
            @can('manage', \App\Models\Part\PartKeyword::class)
                <x-menu.item label="View/Edit Part Keywords" link="{{route('admin.part-keywords.index')}}" />
            @endcan
        </x-menu.top-level-item>
    @endif
    @if(
        Auth::user()->can('create', \App\Models\User::class) ||
        Auth::user()->can('viewAny', \Spatie\Permission\Models\Role::class)
    )
        <x-menu.top-level-item label="Users">
            @can('create', \App\Models\User::class)
                <x-menu.item label="Add/Edit Users" link="{{route('admin.users.index')}}" />
            @endcan
            @can('viewAny', \Spatie\Permission\Models\Role::class)
                <x-menu.item label="Add/Edit Roles" link="{{route('admin.roles.index')}}" />
            @endcan
        </x-menu.top-level-item>
    @endif
    @can('manage', \App\Models\Document\Document::class)
        <x-menu.top-level-item label="Documentation">
            <x-menu.item label="Add/Edit Documentation" link="{{route('admin.documents.index')}}" />
            <x-menu.item label="Add/Edit Documentation Categories" link="{{route('admin.document-categories.index')}}" />
        </x-menu.top-level-item>
    @endcan
    @can('manage', \App\Models\ReviewSummary::class)
        <x-menu.top-level-item label="Review Summaries" link="{{route('admin.summaries.index')}}" />
    @endcan
</x-menu>
