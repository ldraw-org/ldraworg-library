<x-layout.base title="{{$title ?? Admin}}" menu="admin">
    <x-slot:messages>
        @if (tracker_locked())
            <x-message centered icon type="warning">
                <x-slot:header>
                    The Part Tracker is current locked for submission, editing, and voting
                </x-slot:header>
                This usually happens for parts updates or other maintenance. If it seems like it has been
                an excessibe amount of time, please post on the Parts Tracker Forum.
            </x-message>
        @endif
    </x-slot>
    {{ $slot ?? '' }}
</x-layout.base>    
  