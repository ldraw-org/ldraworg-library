<x-slot:title>
    Torso Shortcut Helper
</x-slot>
<x-slot:menu>
    <x-menu.library />
</x-slot>

<div class="flex flex-col space-y-2">
    <div class="text-2xl font-bold">
        Torso Shortcut Helper
    </div>
    <form wire:submit="submitFile">
        {{ $this->form }}
    </form>
</div>
