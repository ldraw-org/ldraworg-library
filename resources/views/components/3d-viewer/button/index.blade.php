@props(['icon', 'label', 'onClick'])

<div class="border rounded-lg p-1">
    <x-library-icon :icon="$icon" class="w-8" color="fill-gray-500" wire:click="$dispatch('{{ $onClick }}')" title="{{$label}}"/>
</div>