<x-slot:title>
    Part Search
</x-slot>
<div>
    <div class="text-3xl font-bold">
        <span>Part Search</span>
    </div>
    <p>
        Enter separate words to search for files containing all the words (e.g. <em>blue shirt</em> will find all files containing <em>blue</em> and <em>shirt</em>).<br/>
        Surround a phrase with double-quotes to search for that phrase (e.g. <em>"blue shirt"</em> will find all files containing <em>blue shirt</em>).<br/>
        Quoted and unquoted search words may be combined (e.g. <em>"blue shirt" jacket</em> will find files containing <em>blue shirt</em> and <em>jacket</em>).
    </p>
    <form wire:submit="doSearch">
        {{ $this->form }}

        <x-filament::button type="submit">
            Submit
        </x-filament::button>
    </form>
    <livewire:tables.search-parts 
        unofficial
        wire:model="data"
    />
    <livewire:tables.search-parts 
        wire:model="data"
    />
</div>
