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
                icon="fas-undo"
                size="lg"
                label="Normal mode"
                class="border"
                wire:click="$dispatch('ldbi-default-mode')"
            />
            <x-filament::icon-button
                icon="fas-dot-circle"
                size="lg"
                label="Toggle Stud Logos"
                class="border"
                wire:click="$dispatch('ldbi-stud-logos')"
            />
            <x-filament::icon-button
                icon="fas-eye"
                size="lg"
                label="Toggle Photo Mode"
                class="border"
                wire:click="$dispatch('ldbi-physical-mode')"
            />
        </div>
        <div id="ldbi-container" class="border w-full h-[80vh]"> 
            <canvas id="ldbi-canvas" class="size-full"></canvas>
        </div>
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
        <x-layout.ldbi-scripts />
        <script type="text/javascript">
            var scene;
        </script>    
        @script
        <script>
            LDR.Options.bgColor = 0xFFFFFF;

            LDR.Colors.envMapPrefix = '/assets/ldbi/textures/cube/';    
            LDR.Colors.textureMaterialPrefix = '/assets/ldbi/textures/materials/';

            $wire.on('render-model', (modal) => {
                let idToUrl = function(id) {
                    if ($wire.parts[id]) {
                        return [$wire.parts[id]];
                    }
                    else {
                        return [id];
                    }
                };

                let idToTextureUrl = function(id) {
                    if ($wire.parts[id]) {
                        return $wire.parts[id];
                    }
                    else {
                        return id;
                    }
                };
                if (WEBGL.isWebGLAvailable()) {
                    LDR.Colors.load(() => {
                        if (scene) {
                            scene = null;
                        }
                        scene = new LDrawOrg.Model(
                            document.getElementById('ldbi-canvas'), 
                            $wire.modeltext,
                            {idToUrl: idToUrl, idToTextureUrl: idToTextureUrl}
                        );
                    },() => {},$wire.parts['ldconfig.ldr']);
                    window.addEventListener('resize', () => scene.onChange());
                }
            });
            $wire.on('ldbi-default-mode', () => {
                scene.default_mode();
            });
            $wire.on('ldbi-stud-logos', () => {
                if (LDR.Options.studLogo == 1) {
                    LDR.Options.studLogo = 0;
                } else {
                    LDR.Options.studLogo = 1;
                }
                scene.reload();
            });
            $wire.on('ldbi-physical-mode', () => {
                if (scene.loader.physicalRenderingAge > 0) {
                    scene.setPhysicalRenderingAge(0);
                }
                else {
                    scene.setPhysicalRenderingAge(20);
                }
            });
        </script>
        @endscript
    @endpush
</div>
