@props(['target' => 'doSubmit'])
<x-filament::button type="submit">
    <x-filament::loading-indicator wire:loading wire:target="{{$target}}" class="h-5 w-5" />
    Submit
</x-filament::button>
