<div>
    <x-slot:title>LDraw.org Official Model Repository - Add Model</x-slot>
    <x-slot:breadcrumbs>
      <x-breadcrumb-item class="active" item="Add Model" />
    </x-slot>

    {{ $this->table }}

    <x-filament::modal id="ldbi" alignment="center" width="7xl" lazy>
        <x-slot name="heading">
            3D View
        </x-slot>
        <div class="flex flex-col space-y-2">
            <div class="flex gap-2">
                <x-3d-viewer.button.normal />
                <x-3d-viewer.button.studlogo />
                <x-3d-viewer.button.photo />
            </div>
            <div class="flex flex-col w-full h-full">
                <x-3d-viewer class="border w-full h-[80vh]" partname="model.ldr" modeltype="user" />
            </div>
        </div>
    </x-filament::modal>

    @push('scripts')
        @script
        <script>
            $wire.on('open-modal', (modal) => {
                if (scene) {
                    scene.clear();
                    scene = null;
                }
                parts = $wire.parts;
                partname = 'model.ldr';
                $wire.dispatch('ldbi-render-model');
            });
        </script>
        @endscript
    @endpush

</div>
