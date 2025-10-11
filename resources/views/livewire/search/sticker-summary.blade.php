<x-slot:title>
    Sticker Sheet Search
</x-slot>
<div> 
    <div class="text-3xl font-bold">
        <span>Sticker Sheet Part Search</span>
    </div>  
    <form class="p-2" wire:change="doSearch">
        {{ $this->form }}
    </form>
    <div class="space-y-2">
        @if (!is_null($parts))
            <div class="rounded text-xl font-bold bg-gray-200 p-2">Stickers</div>
            <x-part.grid :parts="$parts->where('category.category', 'Sticker')" />
            <div class="rounded text-xl font-bold bg-gray-200 p-2">Shortcuts</div>
            <x-part.grid :parts="$parts->where('category.category', '<>', 'Sticker')" />
        @endif
    </div>
</div>