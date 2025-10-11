<x-slot name="title">{{$title}}</x-slot>
<x-slot:menu>
    @if($menu === 'admin')
        <x-menu.admin />
    @else
        <x-menu.library />
    @endif
</x-slot>
<div>
    {{ $this->table }}
</div>
