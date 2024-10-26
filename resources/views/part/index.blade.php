<x-layout.tracker>
    <x-slot:title>Parts List</x-slot>
    <x-slot:breadcrumbs>
        <x-breadcrumb-item class="active" item="Parts List" />
    </x-slot>
    <div class="text-xl font-bold">
        Parts List
    </div>  
    <livewire:tables.part-list-table />
</x-layout.tracker>