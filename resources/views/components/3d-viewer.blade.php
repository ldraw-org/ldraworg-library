@props(['partname' => '', 'parts' => []])

<div id="ldbi-container" {{ $attributes }}>
    <canvas id="ldbi-canvas" class="size-full"></canvas>
</div>
@push('scripts')
    <x-layout.ldbi-scripts />
    <script type="text/javascript">
        var scene = {};
        var parts = {{ Js::from($parts) }};
        var partname = "{{$partname}}";
        LDR.Options.bgColor = 0xFFFFFF;

        LDR.Colors.envMapPrefix = '/assets/ldbi/textures/cube/';
        LDR.Colors.textureMaterialPrefix = '/assets/ldbi/textures/materials/';

        const OFFICIAL_DIR = '/library/official', UNOFFICIAL_DIR = '/library/unofficial';
        var idToUrl = function(id) {
            if (parts[id]) {
                return [parts[id]];
            } else if(id.startsWith('48/') || id.startsWith('8/')) {
                return [OFFICIAL_DIR + '/p/' + id,
                        UNOFFICIAL_DIR + '/p/' + id];
            } else if(id.startsWith('s/')) {
                return [OFFICIAL_DIR + '/parts/' + id,
                        UNOFFICIAL_DIR + '/parts/' + id];
            } else if(id.match(/[a-z][a-z].*\.dat/)) {
                return [OFFICIAL_DIR + '/p/' + id,
                        OFFICIAL_DIR + '/parts/' + id,
                        UNOFFICIAL_DIR + '/p/' + id,
                        UNOFFICIAL_DIR + '/parts/' + id];
            }
            return [OFFICIAL_DIR + '/parts/' + id,
                        OFFICIAL_DIR + '/p/' + id,
                        UNOFFICIAL_DIR + '/parts/' + id,
                        UNOFFICIAL_DIR + '/p/' + id];
        };

        var idToTextureUrl = function(id) {
            if (parts[id]) {
                return parts[id];
            }
            return id;
        };

        var renderModel = function() {
            if (WEBGL.isWebGLAvailable()) {
                LDR.Colors.load(() => {
                    if (scene) {
                        scene = null;
                    }
                    scene = new LDrawOrg.Model(
                        document.getElementById('ldbi-canvas'),
                        partname,
                        {idToUrl: idToUrl, idToTextureUrl: idToTextureUrl}
                    );
                },() => {},parts['ldconfig.ldr']);
                addEventListener('resize', () => scene.onChange());
            }
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('ldbi-render-model', (event) => {
                renderModel();
            });
            Livewire.on('ldbi-default-mode', (event) => {
                scene.default_mode();
            });
            Livewire.on('ldbi-physical-mode', (event) => {
                if (scene.loader.physicalRenderingAge > 0) {
                    scene.setPhysicalRenderingAge(0);
                }
                else {
                    scene.setPhysicalRenderingAge(20);
                }
            });
            Livewire.on('ldbi-harlequin-mode', (event) => {
                scene.harlequin_mode();
            });
            Livewire.on('ldbi-bfc-mode', (event) => {
                scene.bfc_mode();
            });
            Livewire.on('ldbi-stud-logos', (event) => {
                if (LDR.Options.studLogo == 1) {
                    LDR.Options.studLogo = 0;
                } else {
                    LDR.Options.studLogo = 1;
                }
                scene.reload();
            });
            Livewire.on('ldbi-show-origin', (event) => {
                scene.axesHelper.visible = !scene.axesHelper.visible;
                scene.reload();
            });
        });

    </script>
@endpush
