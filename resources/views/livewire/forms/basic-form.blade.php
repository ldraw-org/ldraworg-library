<form class="space-y-2" method="POST" wire:submit="{{ $submitRoute }}">
    {{ $this->form }}
    <x-filament::button type="submit">
        <x-filament::loading-indicator wire:loading class="h-5 w-5" />
        Submit
    </x-filament::button>
</form>

