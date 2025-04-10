<x-slot:title>
    LDraw Model Viewer
</x-slot>
<x-slot:menu>
    <x-menu.library />
</x-slot>
<x-slot:breadcrumbs>
    <x-breadcrumb-item class="active" item="LDraw Model Viewer" />
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
    <div class="flex flex-col w-full h-full">
        <div class="flex flex-row space-x-2 p-2 mb-2">
            <x-filament::icon-button
                icon="mdi-refresh"
                size="lg"
                label="Normal mode"
                class="border"
                wire:click="$dispatch('ldbi-default-mode')"
            />
            <x-filament::icon-button
                icon="mdi-record-circle"
                size="lg"
                label="Toggle Stud Logos"
                class="border"
                wire:click="$dispatch('ldbi-stud-logos')"
            />
            <x-filament::icon-button
                icon="mdi-eye"
                size="lg"
                label="Toggle Photo Mode"
                class="border"
                wire:click="$dispatch('ldbi-physical-mode')"
            />
        </div>
        <x-3d-viewer class="border w-full h-[80vh]" partname="model.ldr" modeltype="user" />
    </div>

    <div class="border rounded p-2">
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
