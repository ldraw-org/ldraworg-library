<x-slot name="title">User Dashboard</x-slot>
<x-slot:menu>
    <x-menu.library />
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="User Dashboard" />
</x-slot>    
<div>
    <x-filament::tabs class="p-2">
        <x-filament::tabs.item 
            :active="$activeTab === 'user-parts'"
            wire:click="$set('activeTab', 'user-parts')"
        >
            My Submits
        </x-filament::tabs.item>
        <x-filament::tabs.item
            :active="$activeTab === 'user-part-events'"
            wire:click="$set('activeTab', 'user-part-events')"
        >
            Events on my submits
        </x-filament::tabs.item>
    
        <x-filament::tabs.item
            :active="$activeTab === 'review-list'"
            wire:click="$set('activeTab', 'review-list')"
        >
            Parts for My Review
        </x-filament::tabs.item>
    </x-filament::tabs>
    <x-filament::loading-indicator wire:loading class="h-5 w-5" />
    <div wire:loading class="p-2">Loading Table...</div>
    @switch($activeTab)
        @case('user-parts')
            <livewire:tables.user-parts-table />
            @break
        @case('user-part-events')
            <livewire:tables.user-part-events-table />
        @break 
        @case('review-list')
            <livewire:tables.part-ready-for-user-table />
            @break
    @endswitch
</div>