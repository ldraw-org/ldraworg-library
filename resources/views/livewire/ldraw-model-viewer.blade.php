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
    <x-3d-viewer class="border border-gray-200 rounded-lg overflow-hidden w-full h-[calc(100dvh-12rem)]" main-model-name="model.ldr" model-type="user" />
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
</div>
