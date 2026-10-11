<x-layout.base title="{{$title ?? 'Admin' }}" menu="admin">
    <x-slot:messages>
        <x-message.tracker-locked />
    </x-slot>
    {{ $slot }}
</x-layout.base>
