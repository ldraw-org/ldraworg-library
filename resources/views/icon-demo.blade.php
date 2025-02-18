<x-layout.base>
    <x-slot:title>
        LDraw.org Icon and Colors Demo
    </x-slot>
    <x-slot:menu>
        <x-menu.library />
    </x-slot>
    <div class="space-y-2">
        <p>
            Note: Colors listed are from the standard tailwind v3 palette<br>
            <a class="underline decoration-dotted hover:decoration-solid" href="https://v3.tailwindcss.com/docs/customizing-colors">https://v3.tailwindcss.com/docs/customizing-colors</a>
        </p>
        <div class="text-xl font-bold">Event Type Icons</div>
        @foreach(\App\Enums\EventType::cases() as $et)
            @empty($et->icon())
                @continue
            @endempty
            <div class="flex flex-row space-x-2 pl-4">
                <x-dynamic-component :component="$et->icon()" class="{{$et->iconColor()}} w-8" />
                <div class="font-bold">{{$et->label()}} ({{str_replace('fill-', '',$et->iconColor())}})</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-tools class="w-8 fill-green-400" />
            <div class="font-bold">Fix (green-400) (Used as modifier on bottom right) </div>
        </div>
        <div class="text-xl font-bold">Vote Type Icons</div>
        @foreach(\App\Enums\VoteType::cases() as $vt)
            <div class="flex flex-row space-x-2 pl-4">
                <x-dynamic-component :component="$vt->icon()" class="{{$vt->iconColor()}} w-8" />
                @if ($vt != \App\Enums\VoteType::Comment && $vt != \App\Enums\VoteType::CancelVote)
                    <x-fas-user-circle class="{{$vt->iconColor()}} w-8" />
                @endif
                <div class="font-bold">{{$vt->label()}} ({{str_replace('fill-', '',$vt->iconColor())}})</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-square class="fill-white w-8" />
            <x-fas-user-circle class="fill-gray-400 w-8" />
            <div class="font-bold">No Recorded Vote (gray-400)</div>
        </div>
        <div class="text-xl font-bold">Part Status Icons</div>
        @foreach(\App\Enums\PartStatus::cases() as $ps)
            <div class="flex flex-row space-x-2 pl-4">
                <x-fas-square class="{{$ps->iconColor()}} w-8" />
                <div class="font-bold">{{$ps->label()}} ({{str_replace('fill-', '',$ps->iconColor())}})</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-award title="Official" class="w-8 text-blue-800" />
            <div class="font-bold">Official (blue-800)</div>
        </div>
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-exclamation-triangle title="Not releaseable" class="w-8 text-yellow-800" />
            <div class="font-bold">Not Releaseable (yellow-800)</div>
        </div>
</x-layout.base>
