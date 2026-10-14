// Based on Scene.js in the buildinginstructions.js suite developed by Lasse Deleuran
// https://github.com/LasseD/buildinginstructions.js

'use strict';

var LDrawOrg = {};

LDrawOrg.Model = function(canvas, model, options) {
    this.options = options || {};
    this.modelColor = this.options.color || 16;
    this.onStartLoad = this.options.onStartLoad || function() {};
    this.onLoaded = this.options.onLoaded || function() {};
    this.container = this.options.container || canvas.parentNode;

    // Prevent rebuilding aspect of the scene without explicitly meaning to
    this._built = false;
    this._zoomSet = false;

    this.size = {w: 1, l: 1, h: 1, diam: 1};
    this.directionalLights = [];
    this.baseObject = null;
    this.mc = null;
    this.composer = null;

    this.scene = new THREE.Scene();

    // Renderer
    this.renderer = new THREE.WebGLRenderer({canvas: canvas, antialias: true});
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.renderer.shadowMap.enabled = true;
    this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    // Camera and controls
    this.camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0.1, 1000000);
    this.orbitControls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
    this.orbitControls.addEventListener('change', () => this.render());
    this.orbitControls.handleKeys = false;
    this.orbitControls.screenSpacePanning = true;

    // Axes helper (hidden by default)
    this.axesHelper = new THREE.AxesHelper(20);
    this.axesHelper.matrixAutoUpdate = false;
    this.axesHelper.visible = false;
    this.axesHelper.reset = () => {
        if (!this.baseObject) {
            return;
        }
        this.axesHelper.matrix.copy(this.baseObject.matrixWorld);
        this.axesHelper.updateMatrixWorld(true);
    };

    // Ambient lighting
    this.scene.add(new THREE.AmbientLight(0x656565));
    this.scene.add(new THREE.HemisphereLight(0xF4F4FB, 0x30302B, 0.65));

    // Loader
    this.loader = new THREE.LDRLoader(() => this.onLoad(), null, {});
    if (this.options.idToUrl) { this.loader.idToUrl = this.options.idToUrl; }
    if (this.options.idToTextureUrl) { this.loader.idToTextureUrl = this.options.idToTextureUrl; }
    if (this.options.onProgress) { this.loader.onProgress = this.options.onProgress; }
    this.loader.physicalRenderingAge = this.options.physicalRenderingAge || 0;

    // Keep the canvas sized to its container
    if (typeof ResizeObserver !== 'undefined') {
        this._resizeObserver = new ResizeObserver(() => this.onChange());
        this._resizeObserver.observe(this.container);
    }

    // Save model URL. This can be an actual url or a Data URL
    this.setModel(model);
};

LDrawOrg.Model.prototype.setModel = function(model, reload = true) {
    this._model = model;

    // A new model resets the whole scene, including lights and camera zoom
    if (reload) {
        this._built = false;
        this._zoomSet = false;
        this.reload();
    }
};

LDrawOrg.Model.prototype.setPhysicalRenderingAge = function(physicalRenderingAge, reset = true) {
    this.loader.physicalRenderingAge = physicalRenderingAge;
    if (this._built) {
        this.createLights(); // Shadows are only used in physical mode
    }
    if (reset) {
        this.onLoad();
    }
};

LDrawOrg.Model.prototype.reload = function() {
    this.loader.partTypes = {};
    LDR.Studs.makeGenerators('', LDR.Options.studHighContrast, LDR.Options.studLogo);
    this.loader.load(this._model);
};

LDrawOrg.Model.prototype.build = function() {
    // Figure out max bounds of the model
    const b = this.mc.boundingBox || new THREE.Box3(new THREE.Vector3(), new THREE.Vector3(1, 1, 1));
    const bump = x => Math.max(10, x);
    const w = bump(Math.abs(b.max.x - b.min.x));
    const l = bump(Math.abs(b.max.z - b.min.z));
    const h = bump(Math.abs(b.max.y - b.min.y));
    this.size = {w: w, l: l, h: h, diam: Math.max(w, l, h)};

    this.createLights();

    this.camera.position.set(10000, 7000, 10000);

    this._built = true;
};

// Two lights to illuminate top and bottom of the model
LDrawOrg.Model.prototype.createLights = function() {
    this.removeLights();
    this.addDirectionalLight();
    const light = this.addDirectionalLight();
    light.position.set(-light.position.x, -light.position.y, -light.position.z);
};

LDrawOrg.Model.prototype.removeLights = function() {
    while (this.directionalLights.length > 0) {
        const light = this.directionalLights.pop();
        this.scene.remove(light);
        light.shadow.dispose(); // Frees the shadow map, if one was allocated
    }
};

LDrawOrg.Model.prototype.onLoad = function() {
    // A viewer replaced mid-load must not render into the shared canvas
    if (this._disposed) {
        return;
    }
    this.onStartLoad();

    this.scene.background = new THREE.Color(LDR.Options.bgColor);

    this.clear();
    this.loader.generate(this.modelColor, this.mc);

    if (!this._built) {
        this.build();
    }

    // Center the model on the origin. The collector only creates its bounding
    // box once something is added, so an empty or failed model has none.
    if (this.mc.boundingBox) {
        const center = new THREE.Vector3();
        this.mc.boundingBox.getCenter(center);
        this.baseObject.position.set(-center.x, -center.y, -center.z);
    }

    // Repaint when textures are loaded
    LDR.Colors.loadTextures(() => this.render());

    this.axesHelper.matrix.copy(this.baseObject.matrixWorld);
    this.scene.add(this.axesHelper);

    this.onLoaded();
    this.onChange();
};

LDrawOrg.Model.prototype.clear = function() {
    if (this.baseObject) {
        this.scene.remove(this.baseObject);
    }

    const opaqueObject = new THREE.Group();
    const sixteenObject = new THREE.Group();
    const transObject = new THREE.Group();

    this.baseObject = new THREE.Group();
    // Draw non-trans before trans
    this.baseObject.add(opaqueObject);
    this.baseObject.add(sixteenObject);
    this.baseObject.add(transObject);
    this.scene.add(this.baseObject);

    this.mc = new LDR.MeshCollector(opaqueObject, sixteenObject, transObject);
    this.loader.applyOnPartTypes(pt => delete pt.geometry);
};

LDrawOrg.Model.prototype.render = function() {
    if (this.composer) {
        this.composer.render();
    } else {
        this.renderer.render(this.scene, this.camera);
    }
};

// Resize the canvas and camera to fit the container, then repaint
LDrawOrg.Model.prototype.onChange = function(eleW, eleH) {
    // Nothing to size until a model has been loaded
    if (!this.mc) {
        return;
    }

    const styles = getComputedStyle(this.container);
    const w = eleW || this.container.clientWidth - parseFloat(styles.paddingLeft) - parseFloat(styles.paddingRight);
    const h = eleH || this.container.clientHeight - parseFloat(styles.paddingTop) - parseFloat(styles.paddingBottom);
    if (w <= 0 || h <= 0) {
        return;
    }

    this.renderer.setSize(w, h);
    this.camera.left = -w / 2;
    this.camera.right = w / 2;
    this.camera.top = h / 2;
    this.camera.bottom = -h / 2;

    if (!this._zoomSet) {
        this.camera.zoom = Math.min(h / this.size.diam, w / this.size.diam);
        this._zoomSet = true;
    }

    this.camera.updateProjectionMatrix();
    this.orbitControls.update();

    // Rebuild the composer, releasing the previous one's render targets
    this.disposeComposer();
    this.composer = new THREE.EffectComposer(this.renderer);
    this.composer.addPass(new THREE.RenderPass(this.scene, this.camera));
    if (!this.mc.attachGlowPasses(w, h, this.scene, this.camera, this.composer)) {
        this.disposeComposer();
    }

    this.render();
};

// The EffectComposer bundled with buildinginstructions.js (three.js r139) has no
// dispose(), so release its render targets directly. Newer versions have dispose().
LDrawOrg.Model.prototype.disposeComposer = function() {
    if (!this.composer) {
        return;
    }
    if (typeof this.composer.dispose === 'function') {
        this.composer.dispose();
    } else {
        this.composer.renderTarget1.dispose();
        this.composer.renderTarget2.dispose();
    }
    this.composer = null;
};

LDrawOrg.Model.prototype.harlequin_mode = function() {
    LDR.setMode(2);
    this.onLoad();
};

LDrawOrg.Model.prototype.bfc_mode = function() {
    LDR.setMode(1);
    this.onLoad();
};

LDrawOrg.Model.prototype.default_mode = function() {
    // Restore the default prototypes changed in other modes
    LDR.setMode(0);
    LDR.Options.studLogo = 0;
    this.reload();
};

LDrawOrg.Model.prototype.togglePhysicalRendering = function() {
    this.setPhysicalRenderingAge(this.loader.physicalRenderingAge > 0 ? 0 : 20);
};

LDrawOrg.Model.prototype.toggleStudLogos = function() {
    LDR.Options.studLogo = LDR.Options.studLogo === 1 ? 0 : 1;
    this.reload(); // Stud generators are built in reload()
};

LDrawOrg.Model.prototype.toggleOrigin = function() {
    this.axesHelper.visible = !this.axesHelper.visible;
    this.render();
};

// Shadow maps cost width * height * 4 bytes each, so keep them modest
LDrawOrg.Model.SHADOW_MAP_MAX = 4096;

LDrawOrg.Model.prototype.shadowMapSize = function(factor) {
    const max = Math.min(LDrawOrg.Model.SHADOW_MAP_MAX, this.renderer.capabilities.maxTextureSize);
    return Math.min(Math.floor(factor * this.size.diam), max);
};

LDrawOrg.Model.prototype.addDirectionalLight = function() {
    const dist = this.size.w * 1.5;
    const y = this.size.h;
    const diam = this.size.diam;

    const light = new THREE.DirectionalLight(0xF6E3FF, 0.4);
    light.position.set(-0.05 * dist, y, -0.02 * dist);
    light.lookAt(0, 0, 0);

    // LDRLoader only makes meshes cast and receive shadows in physical mode, so
    // outside it a shadow map would be allocated for nothing.
    light.castShadow = this.loader.physicalRenderingAge > 0;
    if (light.castShadow) {
        light.shadow.mapSize.width = this.shadowMapSize(3);
        light.shadow.mapSize.height = this.shadowMapSize(3);
        light.shadow.camera.near = 0.5;
        light.shadow.camera.far = 3.5 * (dist + y);
        light.shadow.camera.left = -diam;
        light.shadow.camera.right = diam;
        light.shadow.camera.top = diam;
        light.shadow.camera.bottom = -diam;
        light.shadow.radius = 8; // Soft shadow
    }

    this.scene.add(light);
    this.directionalLights.push(light);
    return light;
};

// Release GPU resources and listeners when the viewer is removed from the page
LDrawOrg.Model.prototype.dispose = function() {
    this._disposed = true;
    if (this._resizeObserver) {
        this._resizeObserver.disconnect();
    }
    this.orbitControls.dispose();
    this.disposeComposer();
    // Blank the canvas so a replaced model isn't left frozen on screen
    this.renderer.setClearColor(LDR.Options.bgColor, 1);
    this.renderer.clear();
    this.renderer.dispose();
};
