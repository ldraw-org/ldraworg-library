<x-menu>
    <x-menu.library-dropdown />
    @if(
        Auth::user()->can('manage', \App\Models\Part\PartKeyword::class) ||
        Auth::user()->can(\App\Enums\Permission::SiteSettingsEdit)
    )
        <x-menu.dropdown label="Library Management">
            @can(\App\Enums\Permission::SiteSettingsEdit)
            <x-menu.item label="General Library Settings" link="{{route('admin.settings.index')}}" />
            @endcan
           @can('manage', \App\Models\Part\PartKeyword::class)
                <x-menu.item label="View/Edit Part Keywords" link="{{route('admin.part-keywords.index')}}" />
            @endcan
        </x-menu.dropdown>
    @endif
    @if(
        Auth::user()->can('create', \App\Models\User::class) ||
        Auth::user()->can('viewAny', \Spatie\Permission\Models\Role::class)
    )
        <x-menu.dropdown label="User Management">
            @can('create', \App\Models\User::class)
                <x-menu.item label="Add/Edit Users" link="{{route('admin.users.index')}}" />
            @endcan
            @can('viewAny', \Spatie\Permission\Models\Role::class)
                <x-menu.item label="Add/Edit Roles" link="{{route('admin.roles.index')}}" />
            @endcan
        </x-menu.dropdown>
    @endif
    @can('manage', \App\Models\Document\Document::class)
        <x-menu.dropdown label="Documentation Management">
            <x-menu.item label="Add/Edit Documentation" link="{{route('admin.documents.index')}}" />
            <x-menu.item label="Add/Edit Documentation Categories" link="{{route('admin.document-categories.index')}}" />
        </x-menu.dropdown>
    @endcan
    @can('manage', \App\Models\ReviewSummary\ReviewSummary::class)
        <x-menu.item label="Add/Edit Part Review Summaries" link="{{route('admin.summaries.index')}}" />
    @endcan
</x-menu>
