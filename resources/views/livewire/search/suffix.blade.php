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
    @if($this->patterns->count() === 0 && $this->composites->count() === 0 && $this->shortcuts->count() === 0)
        <div class="rounded border p-2">
            Part Not Found
        </div>
    @else
        <div class="text-xl font-bold p-2">
            Pattern/Composite/Sticker Shortcut Reference for 
        </div>
        <x-filament::tabs class="p-2">
            <x-filament::tabs.item 
                :active="$activeTab === 'patterns'"
                wire:click="$set('activeTab', 'patterns')"
            >
                <x-slot name="badge">
                    {{$this->patterns->count()}}
                </x-slot>
                Patterns
            </x-filament::tabs.item>
            <x-filament::tabs.item
                :active="$activeTab === 'composites'"
                wire:click="$set('activeTab', 'composites')"
            >
                <x-slot name="badge">
                    {{$this->composites->count()}}
                </x-slot>
                Composites
            </x-filament::tabs.item>
        
            <x-filament::tabs.item
                :active="$activeTab === 'shortcuts'"
                wire:click="$set('activeTab', 'shortcuts')"
            >
                <x-slot name="badge">
                    {{$this->shortcuts->count()}}
                </x-slot>
                Shortcuts
            </x-filament::tabs.item>

        </x-filament::tabs>
        
        <div class="rounded border p-2">
            @switch($activeTab)
                @case('patterns')
                    <x-part.grid :parts="$this->patterns" />
                    @break
                @case('composites')
                    <x-part.grid :parts="$this->composites" />
                    @break
                @case('shortcuts')
                    <x-part.grid :parts="$this->shortcuts" />
                    @break
            @endswitch
        </div>
    @endif
</div>
