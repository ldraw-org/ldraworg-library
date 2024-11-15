<x-slot name="title">{{$title}}</x-slot>
<x-slot:menu>
    @if($menu === 'admin')
        <x-menu.admin />
    @else
        <x-menu.library />
    @endif
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="{{$title}}" />
</x-slot>    
<div>
    {{ $this->table }}
</div>
