<x-slot:title>File Editor</x-slot>
<x-slot:menu><x-menu.library /></x-slot>
<div class="flex flex-col space-y-4">
    <form wire:submit="getFile">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading wire:target="getFile" class="h-5 w-5" />
            Load File
        </x-filament::button>
        <x-filament::button wire:click="saveFile">
            <x-filament::loading-indicator wire:loading wire:target="saveFile" class="h-5 w-5" />
            Save
        </x-filament::button>
    </form>
</div>