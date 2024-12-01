<x-accordion id="officialParts">
    <x-slot name="header">
        Creative Commons Attribution License information
    </x-slot>
    <p>
        This part is copyright &copy; {{empty(trim($copyuser->realname)) ? 'LDraw.org' : $copyuser->realname}}<br/>
        Licensed under <x-part.license :license="$copyuser->license->value" /><br>
        <br>
        Edits:<br>
        LDraw.org Parts Tracker,
        @foreach($editusers as $u)
            {{$u->realname}},
        @endforeach
    </p>
</x-accordion>
