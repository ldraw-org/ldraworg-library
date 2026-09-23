@props(['action', 'showFallback' => false, 'fallbackColor' => 'danger', 'fallbackLabel' => ''])

<div {{ $attributes->merge(['class' => 'size-fit']) }}>
@if ($action->isVisible())
    {{ $action }}
@elseif ($showFallback)
    <x-filament::button
        icon="{{ $action->getIcon() }}"
        color="{{ $fallbackColor }}"
    >
        {{ $fallbackLabel }}
    </x-filament::button>
@endif
</div>
