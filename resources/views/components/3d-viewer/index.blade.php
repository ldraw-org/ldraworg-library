@props(['mainModelName' => '', 'modelType' => 'part', 'modelId' => null, 'parts' => []])
<div id="ldbi-container" {{ $attributes }}>
    <canvas id="ldbi-canvas" class="size-full"></canvas>
</div>
@push('scripts')
    <x-3d-viewer.ldbi-scripts />
    <script>
        (() => {
            // What this component was rendered with. A ldbi-render-model event can override any
            // of these, which is how a modal shows a different model each time it opens.
            const defaults = {
                modelType: {{ Js::from($modelType) }},
                modelId: {{ Js::from($modelId) }},
                mainModelName: {{ Js::from($mainModelName) }},
                parts: {{ Js::from($parts) }},
            };
            let parts = defaults.parts;
            let scene = null;

            LDR.Options.bgColor = 0xFFFFFF;
            LDR.Colors.envMapPrefix = '/assets/ldbi/textures/cube/';
            LDR.Colors.textureMaterialPrefix = '/assets/ldbi/textures/materials/';

            const OFFICIAL_DIR = '/library/official', UNOFFICIAL_DIR = '/library/unofficial';

            const idToUrl = (id) => {
                if (parts[id]) {
                    return [parts[id]];
                }
                if (id.startsWith('48/') || id.startsWith('8/')) {
                    return [OFFICIAL_DIR + '/p/' + id, UNOFFICIAL_DIR + '/p/' + id];
                }
                if (id.startsWith('s/')) {
                    return [OFFICIAL_DIR + '/parts/' + id, UNOFFICIAL_DIR + '/parts/' + id];
                }
                if (id.match(/[a-z][a-z].*\.dat/)) {
                    return [OFFICIAL_DIR + '/p/' + id, OFFICIAL_DIR + '/parts/' + id,
                        UNOFFICIAL_DIR + '/p/' + id, UNOFFICIAL_DIR + '/parts/' + id];
                }
                return [OFFICIAL_DIR + '/parts/' + id, OFFICIAL_DIR + '/p/' + id,
                    UNOFFICIAL_DIR + '/parts/' + id, UNOFFICIAL_DIR + '/p/' + id];
            };

            const idToTextureUrl = (id) => parts[id] || id;

            // Checked once: every probe leaves a WebGL context behind until it is garbage collected
            const webglSupported = document.createElement('canvas').getContext('webgl') instanceof WebGLRenderingContext;

            let shownKey = null; // The model currently displayed or loading
            let request = 0;     // Bumped on every model change so stale async work can tell it was superseded

            const fail = (token, what, error) => {
                console.error('3D viewer: could not load ' + what, error);
                if (token === request) {
                    shownKey = null; // Allow a retry
                }
            };

            const createScene = (token, mainModelName) => {
                LDR.Colors.load(() => {
                    if (token !== request) {
                        return;
                    }
                    scene = new LDrawOrg.Model(
                        document.getElementById('ldbi-canvas'),
                        mainModelName,
                        {idToUrl: idToUrl, idToTextureUrl: idToTextureUrl}
                    );
                }, (error) => fail(token, 'colour definitions', error), parts['ldconfig.ldr']);
            };

            // payload (all optional): modelType, modelId, mainModelName, parts, and force to rebuild the same model
            const renderModel = (payload) => {
                if (!webglSupported) {
                    return;
                }
                const model = {...defaults, ...payload};
                const key = [model.modelType, model.modelId, model.mainModelName].join(':');
                if (key === shownKey && !model.force) {
                    return; // Same model (e.g. the modal was reopened): nothing to do
                }
                shownKey = key;
                const token = ++request;

                // Drop the previous model straight away so it isn't left on screen while the new one loads
                if (scene) {
                    scene.dispose();
                    scene = null;
                }
                parts = model.parts;

                if (model.modelType === 'user') {
                    createScene(token, model.mainModelName);
                    return;
                }
                fetch('/webgl/' + model.modelType + '/' + model.modelId)
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (token !== request) {
                            return;
                        }
                        parts = data;
                        createScene(token, model.mainModelName);
                    })
                    .catch((error) => fail(token, 'model data', error));
            };

            document.addEventListener('livewire:init', () => {
                Livewire.on('ldbi-render-model', (payload) => renderModel(payload));
                Livewire.on('ldbi-default-mode', () => scene?.default_mode());
                Livewire.on('ldbi-physical-mode', () => scene?.togglePhysicalRendering());
                Livewire.on('ldbi-harlequin-mode', () => scene?.harlequin_mode());
                Livewire.on('ldbi-bfc-mode', () => scene?.bfc_mode());
                Livewire.on('ldbi-stud-logos', () => scene?.toggleStudLogos());
                Livewire.on('ldbi-show-origin', () => scene?.toggleOrigin());
            });
        })();
    </script>
@endpush
