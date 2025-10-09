<x-slot:title>File Editor</x-slot>
<x-slot:menu><x-menu.library /></x-slot>
<div class="flex flex-col space-y-4">
    <form wire:submit="getFile">
        {{ $this->form }}

        <x-filament::button type="submit">
            Load File
        </x-filament::button>
        <x-filament::button wire:click="saveFile">
            Save
        </x-filament::button>
    </form>
</div>