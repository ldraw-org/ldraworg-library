<x-slot:title>
    Pattern/Composite/Sticker Shortcut Search
</x-slot>
<x-slot:breadcrumbs>
  <x-breadcrumb-item class="active" item="Pattern Search" />
</x-slot>    
<div>
    <form class="p-2" wire:submit="doSearch">
        {{ $this->form }}

        <x-filament::button type="submit">
            Submit
        </x-filament::button>
    </form>
    @if($patterns->count() === 0 && $composites->count() === 0 && $shortcuts->count() === 0)
        <div class="rounded border p-2">
            Part Not Found
        </div>
    @else
        <div class="text-xl font-bold p-2">
            Pattern/Composite/Sticker Shortcut Reference for {{$basepart}}
        </div>
        <x-filament::tabs class="p-2">
            <x-filament::tabs.item 
                :active="$activeTab === 'patterns'"
                wire:click="$set('activeTab', 'patterns')"
            >
                <x-slot name="badge">
                    {{$patterns->count()}}
                </x-slot>
                Patterns
            </x-filament::tabs.item>
            <x-filament::tabs.item
                :active="$activeTab === 'composites'"
                wire:click="$set('activeTab', 'composites')"
            >
                <x-slot name="badge">
                    {{$composites->count()}}
                </x-slot>
                Composites
            </x-filament::tabs.item>
        
            <x-filament::tabs.item
                :active="$activeTab === 'shortcuts'"
                wire:click="$set('activeTab', 'shortcuts')"
            >
                <x-slot name="badge">
                    {{$shortcuts->count()}}
                </x-slot>
                Shortcuts
            </x-filament::tabs.item>

        </x-filament::tabs>
        
        <div @class(["rounded border p-2", 'hidden' => $activeTab !== 'patterns'])>
            @forelse($patterns as $psuffix => $pitems)
                <div class="text-lg font-bold p-2">{{$psuffix}}</div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 items-stretch">
                    @foreach($pitems as $id => $ppart)
                        <x-part.suffixitem :part="$ppart" wire:key="patternpart-{{$id}}" />
                    @endforeach
                </div>
            @empty
                <p>
                    None
                </p>
            @endforelse
        </div>
        <div @class(["rounded border p-2", 'hidden' => $activeTab !== 'composites'])>
            @forelse($composites as $cpart)
                @if($loop->first)
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 items-stretch">
                @endif
                <x-part.suffixitem :part="$cpart" wire:key="{{$cpart->id}}" />
                @if($loop->last)
                    </div>
                @endif
            @empty
                <p>
                    None
                </p>
            @endforelse
        </div>
        <div @class(["rounded border p-2", 'hidden' => $activeTab !== 'shortcuts'])>
            @forelse($shortcuts as $spart)
                @if($loop->first)
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 items-stretch">
                @endif
                <x-part.suffixitem :part="$spart" wire:key="{{$spart->id}}" />
                @if($loop->last)
                    </div>
                @endif
            @empty
                <p>
                    None
                </p>
            @endforelse
        </div>
    @endif
</div>
