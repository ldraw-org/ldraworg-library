<x-slot:title>
    LDConfig Manager
</x-slot>
<x-slot:menu>
    <x-menu.tracker />
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="LDConfig Edit" />
</x-slot>
<div>
    <div class="text-2xl font-bold">
        LDConfig Edit
    </div>
    <form wire:submit="updateFiles">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>
</div>