@props(['icon', 'label', 'onClick'])

<x-filament::icon-button
    icon="{{ $icon->value }}"
    size="lg"
    label="{{ $label }}"
    class="border"
    wire:click="$dispatch('{{ $onClick }}')"
/>
