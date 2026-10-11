<x-layout.base title="{{$title ?? 'Parts Tracker'}}" menu="tracker">
    <x-slot:messages>
        <x-message.tracker-locked />
        <x-message.ca-accept />
    </x-slot>
    {{ $slot }}
</x-layout.base>
