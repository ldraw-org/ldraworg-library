<x-layout.base>
    <x-slot:title>
        LDraw.org Icon and Colors Demo
    </x-slot>
    <x-slot:menu>
        <x-menu.library />
    </x-slot>
    <div class="space-y-2">
        <div class="text-xl font-bold">Event Type Icons</div>
        @foreach(\App\Enums\EventType::cases() as $et)
            @empty($et->icon())
                @continue
            @endempty
            <div class="flex flex-row space-x-2 pl-4">
                <x-dynamic-component :component="$et->icon()" class="{{$et->iconColor()}} w-8" />
                <div class="font-bold">{{$et->label()}}</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-tools class="w-8 fill-green-400" />
            <div class="font-bold">Fix (Used as modifier on bottom right)</div>
        </div>
        <div class="text-xl font-bold">Vote Type Icons</div>
        @foreach(\App\Enums\VoteType::cases() as $vt)
            <div class="flex flex-row space-x-2 pl-4">
                <x-dynamic-component :component="$vt->icon()" class="{{$vt->iconColor()}} w-8" />
                @if ($vt != \App\Enums\VoteType::Comment && $vt != \App\Enums\VoteType::CancelVote)
                    <x-fas-user-circle class="{{$vt->iconColor()}} w-8" />
                @endif
                <div class="font-bold">{{$vt->label()}}</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-square class="fill-white w-8" />
            <x-fas-user-circle class="fill-gray-400 w-8" />
            <div class="font-bold">No Recorded Vote</div>
        </div>
        <div class="text-xl font-bold">Part Status Icons</div>
        @foreach(\App\Enums\PartStatus::cases() as $ps)
            <div class="flex flex-row space-x-2 pl-4">
                <x-fas-square class="{{$ps->iconColor()}} w-8" />
                <div class="font-bold">{{$ps->label()}}</div>
            </div>
        @endforeach
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-award title="Official" class="w-8 text-blue-800" />
            <div class="font-bold">Official</div>
        </div>
        <div class="flex flex-row space-x-2 pl-4">
            <x-fas-exclamation-triangle title="Not releaseable" class="w-8 text-yellow-800" />
            <div class="font-bold">Not Releaseable</div>
        </div>
</x-layout.base>
