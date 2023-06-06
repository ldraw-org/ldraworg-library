<x-layout.base>
    <x-slot name="title">Admin Dashboard</x-slot>
    <x-slot:menu>
      <x-menu.library />
    </x-slot>
    <h3 class="ui header">Admin Dashboard</h3>
    <div class="ui compact menu">
        <a class="item" href="{{route('admin.dashboard')}}">Admin Dashboard</a>
        @can('viewAny', App\Models\User::class) 
        <a class="item" href="{{route('admin.users.index')}}">Add/Edit Users</a>
        @endcan 
        @can('viewAny', App\Models\ReviewSummary::class) 
        <a class="item" href="{{route('admin.review-summaries.index')}}">Add/Edit Review Summaries</a>
        @endcan 
        @can('viewAny', App\Models\Roles::class) 
        <a class="item" href="{{route('admin.roles.index')}}">Add/Edit Roles</a>
        @endcan 
    </div>

    <div class="ui top attached tabular dashboardmenu menu">
      @can('part.flag.delete')
      <a class="item active" data-tab="delete-flagged">Parts Flagged for Deletion</a>
      @endcan
      @can('part.flag.manual-hold')
      <a class="item" data-tab="manual-hold">Parts Administrativly Held</a>
      @endcan
    </div>
    @can('part.flag.delete')
    <div class="ui bottom attached tab segment active" data-tab="delete-flagged">
      <x-part.table :parts="$delete_flags" />
    </div>
    @endcan
    @can('part.flag.manual-hold')
    <div class="ui bottom attached tab segment" data-tab="manual-hold">
        <x-part.table :parts="$manual_hold_flags" />
    </div>
    @endcan
  </x-layout.base>