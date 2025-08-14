<x-slot:title>File Editor</x-slot>
<x-slot:menu><x-menu.library /></x-slot>
<div class="flex flex-col space-y-4">
    <form wire:submit="getFile">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading wire:target="getFile" class="h-5 w-5" />
            Load File
        </x-filament::button>
        <x-filament::button wire:click="saveFile(edit.getValue())">
            <x-filament::loading-indicator wire:loading wire:target="saveFile" class="h-5 w-5" />
            Save
        </x-filament::button>
    </form>
    <div class="h-100 bg-green-500">
        <div id="aceeditor" wire:ignore class="absolute top-0 bottom-0 left-0 right-0 size-full"></div>
    </div>
</div>

@push('scripts')
@vite('resources/js/ace.js')
@endpush

@script
<script>
// Initial Editor Setup
edit = window.ace.edit("aceeditor");

if (edit) {
    edit.session.setTabSize(4);
    edit.setOptions({
        fontFamily: "Courier New",
        fontSize: "10pt",
        useWorker: true,
    });
    edit.setTheme("ace/theme/monokai");
    edit.session.setMode("ace/mode/php");
}

$wire.on('file-loaded', (contents) => {
    edit.session.setValue(contents.contents);
    edit.session.setMode('ace/mode/' + contents.mode);
});
</script>
@endscript
    