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
    @if(is_null($part) && !is_null($basepart))
        <div class="rounded border border-gray-200 p-2">
            Part Not Found
        </div>
    @elseif(!is_null($part))
        <div class="w-fit float-right p-2"><x-part.grid.item :$part show_obsolete /></div>
        <div class="text-xl font-bold">
            Pattern/Composite/Sticker Shortcut Reference for <a class="underline decoration-dotted hover:decoration-solid" href="{{route('parts.show', $part)}}">{{$part->name()}} - {{$part->description}}</a>
        </div>
        <x-filament::tabs class="clear-both p-2">
            <x-filament::tabs.item
                :active="$activeTab === 'patterns'"
                wire:click="$set('activeTab', 'patterns')"
            >
                <x-slot name="badge">
                    {{$part->patterns->whereNull('unofficial_part')->count()}}
                </x-slot>
                Patterns
            </x-filament::tabs.item>
            <x-filament::tabs.item
                :active="$activeTab === 'composites'"
                wire:click="$set('activeTab', 'composites')"
            >
                <x-slot name="badge">
                    {{$part->composites->whereNull('unofficial_part')->count()}}
                </x-slot>
                Composites
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'shortcuts'"
                wire:click="$set('activeTab', 'shortcuts')"
            >
                <x-slot name="badge">
                    {{$part->shortcuts->whereNull('unofficial_part')->count()}}
                </x-slot>
                Shortcuts
            </x-filament::tabs.item>

        </x-filament::tabs>

        <div class="rounded border-gray-200 p-2">
            @switch($activeTab)
                @case('patterns')
                    <x-part.grid :parts="$part->patterns->load('votes', 'official_part')->whereNull('unofficial_part')->sortBy('filename')" />
                    @break
                @case('composites')
                    <x-part.grid :parts="$part->composites->load('votes', 'official_part')->whereNull('unofficial_part')->sortBy('filename')" />
                    @break
                @case('shortcuts')
                    <x-part.grid :parts="$part->shortcuts->load('votes', 'official_part')->whereNull('unofficial_part')->sortBy('filename')" />
                    @break
            @endswitch
        </div>
    @endif
</div>
