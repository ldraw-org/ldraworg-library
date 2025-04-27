<x-layout.base>
    <x-slot:title>{{$title}}</x-slot>
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
    <x-slot:rightlogo>{{asset('/images/banners/tracker.png')}}</x-slot>
    <x-slot:menu>
      <x-menu.tracker />
    </x-slot>
    <x-slot:breadcrumbs>
        @isset($breadcrumbs)
            <x-breadcrumb-item item="Parts Tracker" />
            {{$breadcrumbs}}
        @else   
            <x-breadcrumb-item class="active" item="Parts Tracker" />
        @endisset
    </x-slot>     
    {{ $slot ?? '' }}
</x-layout.base>    
  