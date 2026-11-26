@props(['target' => 'doSubmit'])
<x-filament::button type="submit" wire:target="{{ $target }}">
    Submit
</x-filament::button>
