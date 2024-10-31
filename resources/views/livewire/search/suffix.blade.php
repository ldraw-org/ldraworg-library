<x-slot:title>
    Pattern/Composite/Sticker Shortcut Search
</x-slot>
<x-slot:breadcrumbs>
  <x-breadcrumb-item class="active" item="Pattern Search" />
</x-slot>    
<div>
    <form class="p-2" wire:submit="doSearch">
        {{ $this->form }}

        <x-filament::button type="submit">
            Submit
        </x-filament::button>
    </form>
    @if(is_null($part) && !is_null($basepart))
        <div class="rounded border p-2">
            Part Not Found
        </div>
    @elseif(!is_null($part) && $part->patterns->count() === 0 && $part->composites->count() === 0 && $part->shortcuts->count() === 0)
        <div class="rounded border p-2">
            No Patterns/Composites/Sticker Shortcuts Found
        </div>
    @elseif (!is_null($part) && ($part->patterns->count() !== 0 || $part->composites->count() !== 0 || $part->shortcuts->count() !== 0))
        <div class="text-xl font-bold p-2">
            Pattern/Composite/Sticker Shortcut Reference for {{$part->name()}} - {{$part->description}}
        </div>
        <x-filament::tabs class="p-2">
            <x-filament::tabs.item 
                :active="$activeTab === 'patterns'"
                wire:click="$set('activeTab', 'patterns')"
            >
                <x-slot name="badge">
                    {{$part->patterns->count()}}
                </x-slot>
                Patterns
            </x-filament::tabs.item>
            <x-filament::tabs.item
                :active="$activeTab === 'composites'"
                wire:click="$set('activeTab', 'composites')"
            >
                <x-slot name="badge">
                    {{$part->composites->count()}}
                </x-slot>
                Composites
            </x-filament::tabs.item>
        
            <x-filament::tabs.item
                :active="$activeTab === 'shortcuts'"
                wire:click="$set('activeTab', 'shortcuts')"
            >
                <x-slot name="badge">
                    {{$part->shortcuts->count()}}
                </x-slot>
                Shortcuts
            </x-filament::tabs.item>

        </x-filament::tabs>
        
        <div class="rounded border p-2">
            @switch($activeTab)
                @case('patterns')
                    <x-part.grid :parts="$part->patterns->load('votes', 'official_part')" />
                    @break
                @case('composites')
                    <x-part.grid :parts="$part->composites->load('votes', 'official_part')" />
                    @break
                @case('shortcuts')
                    <x-part.grid :parts="$part->shortcuts->load('votes', 'official_part')" />
                    @break
            @endswitch
        </div>
    @endif
</div>
