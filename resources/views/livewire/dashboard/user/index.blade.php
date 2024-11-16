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
            :active="$activeTab === 'user-votes'"
            wire:click="$set('activeTab', 'user-votes')"
        >
            My Votes
        </x-filament::tabs.item>    
        <x-filament::tabs.item
            :active="$activeTab === 'unumbers'"
            wire:click="$set('activeTab', 'unumbers')"
        >
            My uXXXX Number Reservations
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
        @case('user-votes')
            <livewire:tables.user-votes-table />
            @break 
        @case('unumbers')
            <livewire:tables.user-unknown-numbers-table />
            @break
    @endswitch
</div>