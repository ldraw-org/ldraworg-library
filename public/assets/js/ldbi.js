var part_paths;

let canvas = document.getElementById('model-canvas');
var scene;

LDR.Options.bgColor = 0xFFFFFF;

LDR.Colors.envMapPrefix = '/assets/ldbi/textures/cube/';    
LDR.Colors.textureMaterialPrefix = '/assets/ldbi/textures/materials/';

var idToUrl = function(id) {
  if (part_paths[id]) {
    return [part_paths[id]];
  }
  else {
    return [id];
  }
};

var idToTextureUrl = function(id) {
  if (part_paths[id]) {
    return part_paths[id];
  }
  else {
    return id;
  }
};

var menuClick = function(e) {
  partID = $('#filename').text().replace(/^(parts\/|p\/)/, '');
  if ( !WEBGL.isWebGLAvailable() ) { return; }
  $('.ui.ldbi.modal').modal('show');
  if (!scene) {
    // pre-fetch the paths to the subfiles used to speed up loading
    var posting = $.get( '/api/' + part_id + '/ldbi')
      .done(function( response ) {
        part_paths = response;
        scene = new LDrawOrg.Model(canvas, partID, {idToUrl: idToUrl, idToTextureUrl: idToTextureUrl});
        window.addEventListener('resize', () => scene.onChange(), false);
    });
  }
  return false;  
}

$( function() {
  // Init the modal
  $('.ui.ldbi.modal').modal();
  
  $('.webglview').add('.part-img').on('click', menuClick);

  $('.default-mode').on('click', function(e) {
    $('.stud-logos').removeClass('active');
    $('.stud-logos').siblings();
    scene.default_mode();
    return false;
  });

  $('.harlequin').on('click', function(e) {
    scene.harlequin_mode();
    return false;
  });

  $('.bfc').on('click', function(e) {
    scene.bfc_mode();
    return false;
  });

  $('.stud-logos').on('click', function(e) {
    $(this).toggleClass('active');
    if (LDR.Options.studLogo == 1) {
      LDR.Options.studLogo = 0;
    } else {
      LDR.Options.studLogo = 1;
    }
    scene.reload();
    return false;
  });

  $('.origin').on('click', function(e) {
    $(this).toggleClass('active');
    scene.axesHelper.visible = !scene.axesHelper.visible;
    scene.reload();
    return false;
  });

  $(".physical").on('click', function(e) {
    if (scene.loader.physicalRenderingAge > 0) {
      scene.setPhysicalRenderingAge(0);
    }
    else {
      scene.setPhysicalRenderingAge(20);
    }
  });

});