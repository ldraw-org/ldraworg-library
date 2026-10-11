@auth
    @can('submit', [App\Models\Part\Part::class] )
        @if(Auth::user()->ca_confirm !== true))
            <x-message centered icon type="warning">
                <x-slot:header>
                    You have not accepted the current Contributor's Agreement. You will not be able to
                    submit or edit parts.
                </x-slot:header>
                Visit the <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('tracker.confirmCA.show')}}">CA acceptance page</a> to agree to the new CA:
            </x-message>
        @endif
    @endcan
@endauth
