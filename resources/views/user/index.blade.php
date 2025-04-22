<x-layout.base>
    <x-slot:title>User List</x-slot>
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="User List" />
    </x-slot>
    <x-slot:menu>
        <x-menu.library />
    </x-slot>
    <div class="text-xl font-bold">
        User List
    </div>  
    <livewire:tables.user-table />
</x-layout.base>