<x-layout.base title="{{$title ?? 'Parts Tracker'}}" menu="tracker">
    <x-slot:messages>
        @if (tracker_locked())
            <x-message centered icon type="warning">
                <x-slot:header>
                    The Part Tracker is currently locked for submission, editing, and voting
                </x-slot:header>
                This usually happens for parts updates or other maintenance. If it seems like it has been
                an excessive amount of time, please post on the Parts Tracker Forum.
            </x-message>
        @endif
        @if (Auth::check() && Auth::user()->can('submit', App\Models\Part\Part::class) && Auth::user()->ca_confirm !== true)
            <x-message centered icon type="warning">
                <x-slot:header>
                    You have not confirmed the current Contributor's Agreement. You will not be able to
                    submit or edit parts.
                </x-slot:header>
                Please visits the CA confirm page to agree to the new CA: <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('tracker.confirmCA.show')}}">Confirm the new CA</a>
            </x-message>
        @endif
    </x-slot>
    {{ $slot ?? '' }}
</x-layout.base>    
  