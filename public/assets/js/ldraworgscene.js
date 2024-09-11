// Based on Scene.js in the buildinginstructions.js suite developed by Lasse Delauren
// https://github.com/LasseD/buildinginstructions.js

'use strict';

var LDrawOrg = {};


LDrawOrg.Model = function(canvas, model, options) {
  let self = this;

  // Prevent rebuilding aspect of the scene without expicitly meaning to
  this._built = false;
  this._zoomSet = false;

  // Set options
  this.options = options || {};
  this.modelColor = this.options.color || 16;
  this.onStartLoad = this.options.onStartLoad || function(){};
  this.onLoaded = this.options.onLoaded || function(){};
  this.container = this.options.container || canvas.parentNode;
  
  this.axesHelper = new THREE.AxesHelper(20);
  this.axesHelper.matrixAutoUpdate = false;
  this.axesHelper.visible = false;
  this.axesHelper.reset = function() {
    this.matrix.copy(baseObject.matrixWorld);
    this.updateMatrixWorld(true);	  
  }

  // Save the default prototype functions
  this.defaultLDRGeometry = LDR.LDRGeometry;
  this.defaultfromPartType = LDR.LDRGeometry.prototype.fromPartType;


  this.size = {w:1, l:1, h:1, diam:1};

  // Lights
  this.pointLights = [];
  this.directionalLights = [];
  this.hemisphereLight;
  this.amblight;

  this.scene = new THREE.Scene();

  // Set up renderer:
  this.renderer = new THREE.WebGLRenderer({canvas:canvas, antialias: true});
  this.renderer.setPixelRatio(window.devicePixelRatio);
  this.renderer.shadowMap.enabled = true;
  this.renderer.shadowMap.type = THREE.PCFSoftShadowMap; // Default is PCFShadowMap
  this.renderer.shadowMapSoft = true;

  // Set up camera:
  this.camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0.1, 1000000);
  this.orbitControls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
  this.orbitControls.addEventListener('change', () => self.render());
  this.orbitControls.handleKeys = false;
  this.orbitControls.screenSpacePanning = true;
  
  // Light and background:
  this.amblight = new THREE.AmbientLight( 0x656565 ); // soft white light
  this.scene.add(this.amblight);
  this.hemisphereLight = new THREE.HemisphereLight(0xF4F4FB, 0x30302B, 0.65);
  this.scene.add(this.hemisphereLight);
  
	this.loader = new THREE.LDRLoader(() => this.onLoad(), null, {});
	if (this.options.idToUrl) { this.loader.idToUrl = this.options.idToUrl; }
	if (this.options.idToTextureUrl) { this.loader.idToTextureUrl = this.options.idToTextureUrl; }
	if (this.options.onProgress) { this.loader.onProgress = this.options.onProgress; }
  this.loader.physicalRenderingAge = this.options.physicalRenderingAge || 0;

  // Save model URL. This can be an actual url or a Data URL
  this.setModel(model);
}

LDrawOrg.Model.prototype.setModel = function(model, reload = true) {
  this._model = model;

  // Since the model was changed, the whole scene including 
  // lights and camera positioning/zoom need to be reset
  if (reload) {
    this._built = false;
    this._zoomSet = false;
  	this.reload(this._model);
  }
}

LDrawOrg.Model.prototype.setPhysicalRenderingAge = function(physicalRenderingAge, reset = true) {
  this.loader.physicalRenderingAge = physicalRenderingAge;
  if (reset) {
    this.onLoad();
  }
}

LDrawOrg.Model.prototype.reload = function() {
  this.loader.partTypes = {};
	LDR.Studs.makeGenerators('', LDR.Options.studHighContrast, LDR.Options.studLogo);
	this.loader.load(this._model);
}

LDrawOrg.Model.prototype.build = function() {
  // Remove lights if they were added
	while(this.directionalLights.length > 0) {
    this.scene.remove(this.directionalLights.pop());
  }
	while(this.pointLights.length > 0) {
    this.scene.remove(this.pointLights.pop());
  }
  
  // Figure out max bounds of the model
	let b = this.mc.boundingBox || new THREE.Box3(new THREE.Vector3(), new THREE.Vector3(1,1,1)); // To build scene around.
  let bump = x => Math.max(10, x);
  let w = bump(Math.abs(b.max.x-b.min.x)), l = bump(Math.abs(b.max.z-b.min.z)), h = bump(Math.abs(b.max.y-b.min.y));
  this.size = {w:w, l:l, h:h, diam:Math.max(w,l,h)};

  // Add 2 lights to illumate top and bottom of model.
	this.addDirectionalLight();
	let light = this.addDirectionalLight();
	light.position.set(-light.position.x,-light.position.y,-light.position.z);

  this.camera.position.set(10000, 7000, 10000);
  
	this._built = true;
}  

LDrawOrg.Model.prototype.onLoad = function() {
  this.onStartLoad();
  
  this.scene.background = new THREE.Color(LDR.Options.bgColor);
//	LDR.Studs.makeGenerators('', LDR.Options.studHighContrast, LDR.Options.studLogo);

	this.clear();
	this.loader.generate(this.modelColor, this.mc);

	if (!this._built) {
    this.build();
	}

  // Center the model on the origin
  var elementCenter = new THREE.Vector3();
  this.mc.boundingBox.getCenter(elementCenter);
  this.baseObject.position.set(-elementCenter.x, -elementCenter.y, -elementCenter.z);

	LDR.Colors.loadTextures(() => scene.render()); // Ensure that we repaint when textures are loaded.
	this.axesHelper.matrix.copy(this.baseObject.matrixWorld);
	this.scene.add(this.axesHelper);
  this.onLoaded();
	this.onChange(); // Render
}

LDrawOrg.Model.prototype.clear = function() {
  if (this.baseObject) {
    this.scene.remove(this.baseObject);
  } 
  this.baseObject = new THREE.Group();
  let opaqueObject = new THREE.Group();
  let sixteenObject = new THREE.Group();
  let transObject = new THREE.Group();
  this.baseObject.add(opaqueObject); // Draw non-trans before trans.
  this.baseObject.add(sixteenObject);
  this.baseObject.add(transObject);
  this.scene.add(this.baseObject);
  this.mc = new LDR.MeshCollector(opaqueObject, sixteenObject, transObject);
  this.loader.applyOnPartTypes(pt => delete pt.geometry);
}  

LDrawOrg.Model.prototype.render = function() {
  if(this.composer) {
      this.composer.render();
  }
  else {
      this.renderer.render(this.scene, this.camera);
  }
}

LDrawOrg.Model.prototype.onChange = function(eleW, eleH) {
  var styles = getComputedStyle(this.container);
  var w = eleW || this.container.clientWidth - parseFloat(styles.paddingLeft) - parseFloat(styles.paddingRight);
  var h = eleH || this.container.clientHeight - parseFloat(styles.paddingTop) - parseFloat(styles.paddingBottom);

  this.renderer.setSize(w, h);
  this.camera.left = -w/2;
  this.camera.right = w/2;
  this.camera.top = h/2;
  this.camera.bottom = -h/2;
  
	if (!this._zoomSet) {
    let cameraZoom = Math.min(Math.abs(this.camera.top-this.camera.bottom)/this.size.diam, Math.abs(this.camera.right-this.camera.left)/this.size.diam);
    this.camera.zoom = cameraZoom;
    this._zoomSet = true;
	}
	
  this.camera.updateProjectionMatrix();
  this.orbitControls.update();
  
  this.composer = new THREE.EffectComposer(this.renderer);
  this.composer.addPass(new THREE.RenderPass(this.scene, this.camera));
  if(!this.mc.attachGlowPasses(w, h, this.scene, this.camera, this.composer)) {
      this.composer = null;
  }

  this.render();
}

LDrawOrg.Model.prototype.harlequin_mode = function() {
  LDR.setMode(2);
  this.onLoad();
}

LDrawOrg.Model.prototype.bfc_mode = function() {
  LDR.setMode(1);
  this.onLoad();
}

LDrawOrg.Model.prototype.default_mode = function() {
  // Restore the default prototypes changed in other modes
  LDR.setMode(0);
  LDR.Options.studLogo = 0;
  this.reload();
}

LDrawOrg.Model.prototype.addPointLight = function() {
  const color = 0xF6E3FF;
  const intensity = 0.7;
  const dist = this.size.w*1.5;
  const y = this.size.h*2;
  let light = new THREE.PointLight(color, intensity, 2*(dist+y));
  
  light.castShadow = true;
  light.shadow.mapSize.width = Math.floor(2.5*this.size.diam); // Adjust according to size!
  light.shadow.mapSize.height = Math.floor(2.5*this.size.diam);
  light.shadow.camera.near = 0.5;
  light.shadow.camera.far = 2*(dist+y);
  light.shadow.radius = 8; // Soft shadow
  light.position.set(dist, y, dist);

  this.scene.add(light);
  this.pointLights.push(light);
  return light;
}

LDrawOrg.Model.prototype.addDirectionalLight = function() {
  const dist = this.size.w*1.5;
  const y = this.size.h;
  const diam = this.size.diam;

  let light = new THREE.DirectionalLight(0xF6E3FF, 0.4); // color, intensity
  light.position.set(-0.05*dist, y, -0.02*dist);
  light.lookAt(0,0,0);
  
  light.castShadow = true;
  light.shadow.mapSize.width = Math.floor(3*diam); // Adjust according to size!
  light.shadow.mapSize.height = Math.floor(3*diam);
  light.shadow.camera.near = 0.5;
  light.shadow.camera.far = 3.5*(dist+y);
  light.shadow.camera.left = -diam;
  light.shadow.camera.right = diam;
  light.shadow.camera.top = diam;
  light.shadow.camera.bottom = -diam;
  light.shadow.radius = 8; // Soft shadow

  this.scene.add(light);
  this.directionalLights.push(light);
  return light;
}

LDrawOrg.LightIdx = 1;
LDrawOrg.Model.prototype.registerLight = function(light, folder) {
  let self = this;
  let h = light.type === "PointLight" ?
    new THREE.PointLightHelper(light, 5, 0xFF0000) :
    new THREE.DirectionalLightHelper(light, 5, 0x0000FF);
  h.visible = false;
  this.scene.add(h);

  let size = this.size;

  function r() {
      light.lookAt(0,0,0);
      self.render();
  }

  let c = folder.addFolder('Light ' + ENV.LightIdx++);
  c.add(light.position, 'x', -10*size.w, 10*size.w).onChange(r);
  c.add(light.position, 'y', -size.h, 5*size.h).onChange(r);
  c.add(light.position, 'z', -10*size.l, 10*size.l).onChange(r);
  c.add(light, 'intensity', 0.0, 1.0).onChange(r);
  function setColor(v) {
      light.color = new THREE.Color(v);
      h.update();
      r();
  }
  let options = {
      color: '#FFFFFF',
      Remove: function(){self.scene.remove(light); self.scene.remove(h); folder.removeFolder(c); r();},
  };
  c.addColor(options, 'color').onChange(setColor);
  c.add(h, 'visible').name('show helper').onChange(r);
  c.add(options, 'Remove');
  c.open();
}
