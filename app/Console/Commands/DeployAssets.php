<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:deploy-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy non-vite js assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resource_assets = [
            'js/WebGL.js',
            'js/ldraworgscene.js',
            'ldbi/js/three.min.js',
            'ldbi/js/OrbitControls.js',
            'ldbi/js/CopyShader.js',
            'ldbi/js/Pass.js',
            'ldbi/js/OutlinePass.js',
            'ldbi/js/ShaderPass.js',
            'ldbi/js/RenderPass.js',
            'ldbi/js/EffectComposer.js',
            'ldbi/js/ClientStorage.js',
            'ldbi/js/LDROptions.js',
            'ldbi/js/LDRShaders.js',
            'ldbi/js/LDRColorMaterials.js',
            'ldbi/js/LDRGeometries.js',
            'ldbi/js/LDRBFCGeometries.js',
            'ldbi/js/LDRMeasurer.js',
            'ldbi/js/LDRLoader.js',
            'ldbi/js/LDRGenerator.js',
            'ldbi/js/LDRStuds.js',
            'ldbi/js/VertexNormalsHelper.js',
            'ldbi/textures/cube/nx.jpg',
            'ldbi/textures/cube/ny.jpg',
            'ldbi/textures/cube/nz.jpg',
            'ldbi/textures/cube/px.jpg',
            'ldbi/textures/cube/py.jpg',
            'ldbi/textures/cube/pz.jpg',
            'ldbi/textures/materials/abs.png',
            'ldbi/textures/materials/chrome.png',
            'ldbi/textures/materials/glitter.png',
            'ldbi/textures/materials/metal.png',
            'ldbi/textures/materials/pearl.png',
            'ldbi/textures/materials/rubber.png',
            'ldbi/textures/materials/speckle.png',
        ];
        foreach ($resource_assets as $asset) {
            $file = file_get_contents(resource_path($asset));
            file_put_contents(public_path("assets/{$asset}"), $file);
        }
    }
}
