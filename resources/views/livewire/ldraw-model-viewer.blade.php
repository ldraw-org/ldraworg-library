<x-slot:title>
    LDraw Model Viewer
</x-slot>
<div class="flex flex-col space-y-2">
    <div class="text-2xl font-bold">
        LDraw Model Viewer
    </div>
    <form wire:submit="makeModel">
        {{ $this->form }}

        <x-filament::button type="submit">
            <x-filament::loading-indicator wire:loading wire:target="makeModel" class="h-5 w-5" />
            Submit
        </x-filament::button>
    </form>
    <div class="flex flex-col space-y-2">
        <div class="flex gap-2">
            <x-3d-viewer.button.normal />
            <x-3d-viewer.button.studlogo />
            <x-3d-viewer.button.photo />
        </div>
        <div class="flex flex-col w-full h-full">
            <x-3d-viewer class="border border-gray-200 w-full h-[80vh]" partname="model.ldr" modeltype="user" />
        </div>
    </div>
    <div class="border border-gray-200 rounded p-2">
        <p>
            All parts used in the file submitted to the model viewer must be embedded in the MPD,
            be present in the Official Library, or listed on the Parts Tracker. Official parts
            will be given priority over unoffical parts.
        </p>
        <p>
            The model submitted here is uploaded to LDraw.org for processing but is
            <strong>not</strong> permanently stored.
        </p>
    </div>

    @push('scripts')
        @script
        <script>
            $wire.on('render-model', (event) => {
                parts = $wire.parts;
                partname = 'model.ldr';
                $wire.dispatch('ldbi-render-model');
           });
        </script>
        @endscript
    @endpush

</div>
