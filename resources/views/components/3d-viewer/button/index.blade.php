@props(['icon', 'label', 'click'])

<div class="border border-gray-200 rounded-lg p-1 w-min">
    <x-library-icon :icon="$icon" class="w-8" color="fill-gray-500" wire:click="$dispatch('{{ $click }}')" title="{{$label}}"/>
</div>
