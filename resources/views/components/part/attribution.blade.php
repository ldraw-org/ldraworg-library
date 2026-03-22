<x-accordion>
    <x-slot name="header">
        Creative Commons Attribution License information
    </x-slot>

    <p>
        This part is copyright &copy;
        {{ $copyuser->realname ?: 'LDraw.org' }}<br>

        Licensed under
        <x-part.license :license="$copyuser->license->value" /><br>

        @if($editusers->isNotEmpty())
            <br>
            Edits:<br>
            LDraw.org Parts Tracker,
            {{ $editusers->pluck('realname')->join(', ') }}
        @endif
    </p>
</x-accordion>
