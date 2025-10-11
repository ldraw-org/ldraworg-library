<x-slot:title>
    LDCad Set PBG Generator
</x-slot>
<x-slot:menu>
    <x-menu.library />
</x-slot>

<div class="flex flex-col space-y-2">
    <div class="text-2xl font-bold">
        LEGO Set LDCad PBG Generator
    </div>
    <form wire:submit="makePbg">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading wire:target="makePbg" class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>

    <div class="grid grid-cols-2 gap-2">
        @if(!is_null($pbg))
        <div class="flex flex-col space-y-2">
            <div class="text-md font-bold">PBG:</div>
            <x-filament::button wire:click="pbgDownload" class="w-fit">
                Download
            </x-filament::button>
            <div class="border border-gray-200 rounded p-2">
                {!! nl2br($pbg) !!}
            </div>
        </div>
        @endif
        @if($hasMessages)
            <div class="flex flex-col space-y-2">
                <div class="text-md font-bold">
                    Messages:
                </div>
                @if($hasErrors)
                    <x-message icon type="error">
                        @foreach($errors as $message)
                            <div>{{$message}}</div>
                        @endforeach
                    </x-message>
                @endif
                @if($hasMissing)
                    <x-message icon type="error">
                        <div>The following Rebrickable parts were not found in the LDraw library or Parts Tracker:</div>
                        @foreach($missing as $message)
                            <div>{!! $message !!}</div>
                        @endforeach
                    </x-message>
                @endif
                @if($hasUnpatterned)
                    <x-message icon type="info">
                        <div>The following Rebrickable patterned parts not in LDraw were substituted for LDraw unpattterned parts:</div>
                        <div class="inline">
                        @foreach($unpatterned as $message)
                            <span>{{$message}}, </span>
                        @endforeach
                        </div>
                    </x-message>
                @endif
            </div>
        @endif
    </div>
    <div class="border border-gray-200 rounded p-2">
        The data used by this generator is provided by <a class="underline decoration-dotted hover:decoration-solid" target="_blank" href="https://www.rebrickable.com">Rebrickable</a>.
        The Rebrickable API limits calls to 1 request/sec. Therefore, large or pattern heavy sets may take some time 
        (2-20 sec) to process. Any errors should be submitted as change requests to Rebrickable.
    </div>
</div>
