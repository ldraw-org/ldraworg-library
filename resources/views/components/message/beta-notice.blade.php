@unless (app()->isProduction())
    <x-message centered icon type="warning">
        <x-slot:header>
            You are on a non-production LDraw.org Library site.
        </x-slot:header>
        For the live version go here: <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://library.ldraw.org">https://library.ldraw.org</a>
    </x-message>
@endunless
