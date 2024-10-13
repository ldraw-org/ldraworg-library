<x-menu>
    <x-menu.library-dropdown />
    @if(
        Auth::user()->can('create', \App\Models\PartCategory::class) ||
        Auth::user()->can('manage', \App\Models\PartKeyword::class) ||
        Auth::user()->can('create', \App\Models\PartLicense::class) ||
        Auth::user()->can('create', \App\Models\PartType::class) ||
        Auth::user()->can('settings.edit')
    )
        <x-menu.dropdown label="Library Management">
            @can('settings.edit')    
            <x-menu.item label="General Library Settings" link="{{route('admin.settings.index')}}" />
            @endcan 
            @can('create', \App\Models\PartCategory::class)    
                <x-menu.item label="View/Add Part Categories" link="{{route('admin.part-categories.index')}}" />
            @endcan 
            @can('create', \App\Models\PartType::class)    
                <x-menu.item label="View/Add Part Types" link="{{route('admin.part-types.index')}}" />
            @endcan
            @can('manage', \App\Models\PartKeyword::class)    
                <x-menu.item label="View/Edit Part Keywords" link="{{route('admin.part-keywords.index')}}" />
            @endcan 
            @can('create', \App\Models\PartLicence::class)    
                <x-menu.item label="View/Add Part Licenses" link="{{route('admin.part-licenses.index')}}" />
            @endcan
        </x-menu.dropdown>
    @endif
    @if(
        Auth::user()->can('create', \App\Models\User::class) ||
        Auth::user()->can('create', \Spatie\Permission\Models\Role::class)
    )    
        <x-menu.dropdown label="User Management">
            @can('create', \App\Models\User::class)    
                <x-menu.item label="Add/Edit Users" link="{{route('admin.users.index')}}" />
            @endcan 
            @can('create', \Spatie\Permission\Models\Role::class)    
                <x-menu.item label="Add/Edit Roles" link="{{route('admin.roles.index')}}" />
            @endcan 
        </x-menu.dropdown>                    
    @endif
    @can('documentation.edit')
        <x-menu.dropdown label="Documentation Management">
            <x-menu.item label="Add/Edit Documentation" link="{{route('admin.documents.index')}}" />
            <x-menu.item label="Add/Edit Documentation Categories" link="{{route('admin.document-categories.index')}}" />
        </x-menu.dropdown>            
    @endcan 
    @can('viewAny', \App\Models\ReviewSummary\ReviewSummary::class)    
        <x-menu.item label="Add/Edit Part Review Summaries" link="{{route('admin.summaries.index')}}" />
    @endcan 
</x-menu>
