<x-slot name="title">Admin Dashboard</x-slot>
<x-slot:menu>
    <x-menu.admin />
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="Admin Dashboard" />
</x-slot>    
<div>
    <x-filament::tabs class="p-2">
        <x-filament::tabs.item 
            :active="$activeTab === 'admin-ready'"
            wire:click="$set('activeTab', 'admin-ready')"
        >
            Parts Ready For Admin Review
        </x-filament::tabs.item>
        <x-filament::tabs.item 
            :active="$activeTab === 'sticker-admin-ready'"
            wire:click="$set('activeTab', 'sticker-admin-ready')"
        >
            Torso/Sticker Shortcuts For Admin Review
        </x-filament::tabs.item>
        <x-filament::tabs.item
            :active="$activeTab === 'delete-flag'"
            wire:click="$set('activeTab', 'delete-flag')"
        >
            Parts Flagged for Deletion
        </x-filament::tabs.item>
    
        <x-filament::tabs.item
            :active="$activeTab === 'official-errors'"
            wire:click="$set('activeTab', 'official-errors')"
        >
            Official Parts with Errors
        </x-filament::tabs.item>
    </x-filament::tabs>
    <x-filament::loading-indicator wire:loading class="h-5 w-5" />
    <div wire:loading class="p-2">Loading Table...</div>
    @switch($activeTab)
        @case('admin-ready')
            <livewire:tables.part-ready-for-admin-table />
            @break
        @case('sticker-admin-ready')
            <livewire:tables.sticker-shortcuts-ready-for-admin-table />
            @break
        @case('delete-flag')
            <livewire:tables.parts-flagged-for-deletion-table />
            @break 
        @case('official-errors')
            <livewire:tables.official-parts-with-errors-table />
            @break
    @endswitch
</div>