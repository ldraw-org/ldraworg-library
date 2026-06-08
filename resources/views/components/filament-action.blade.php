@props(['action', 'showFallback' => false, 'fallbackColor' => 'danger', 'fallbackLabel' => ''])

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
