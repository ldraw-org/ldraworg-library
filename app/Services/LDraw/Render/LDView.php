<?php

namespace App\Services\LDraw\Render;

use App\Services\LDraw\LDrawModelMaker;
use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LDView
{
    protected readonly bool $debug;
    protected OptimizerChain $optimizer;

    public function __construct(
        protected LibrarySettings $settings,
        protected LDrawModelMaker $modelmaker,
    ) {
        $this->debug = config('ldraw.ldview_debug', false);
        $this->optimizer = (new OptimizerChain())->addOptimizer(new Optipng([]));
    }

    public function render(string|Part|OmrModel $part): GDImage
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $ldconfigPath = Storage::disk('library')->path('official/LDConfig.ldr');
        
        // LDview requires a p and parts directory
        $ldrawdir = $tempDir->path("ldraw");
        $tempDir->path("ldraw/parts");
        $tempDir->path("ldraw/p");

        // Store the part as an mpd
        $filename = $tempDir->path("part.mpd");
        $contents = $part instanceof Part ? $this->modelmaker->partMpd($part) : $this->modelmaker->modelMpd($part);
        file_put_contents($filename, $contents);

        // Pull max height, width from settings
        $width = $part instanceof Part ? $this->settings->max_part_render_width : $this->settings->max_model_render_width;
        $height = $part instanceof Part ? $this->settings->max_part_render_height : $this->settings->max_model_render_height;

        $imagepath = $tempDir->path("part.png");
      
        $options = [
            'ProcessLDConfig' => '1',
            'LDConfig' => $ldconfigPath,
            'LDrawDir' => $ldrawdir,
            'SaveWidth' => $width * 2,
            'SaveHeight' => $height * 2,
            'SaveAlpha' => '1',
            'SaveZoomToFit' => '1',
            'SaveSnapshot' => $imagepath,
            'BFC' => '0',
            'FOV' => '0.1',
            'Texmaps' => '1',
            'AutoCrop' => '1',
            'Seams' => '0',
            'SeamWidth' => '0',
            'LightVector' => '-1,1,1',
            'MemoryUsage' => '0',
            'UseSpecular' => '0',
            'TextureStuds' => '0',
            'DefaultColor3' => '0xFFFF80',
            'BackgroundColor3' => '0xFFFFFF',
            'LineSmoothing' => '1',
            'SubduedLighting' => '1',
            'UseQualityStuds' => '1',
            'CheckPartTracker' => '0',
            'ShowHighlightLines' => '1',
            'ConditionalHighlights' => '1',
        ];
      
        $cmdOptions = collect($options)
            ->map(fn (string $value, string $command) => "-{$command}={$value}")
            ->implode(' ');
      
        // Run LDView
        $ldviewcmd = "ldview {$filename} {$cmdOptions}";
        if ($this->debug) {
            Log::debug($ldviewcmd);
        }

        $result = Process::run($ldviewcmd);
        if ($this->debug) {
            Log::debug($result->output());
            Log::debug($result->errorOutput());
            Storage::put("/debug/part.mpd", file_get_contents($filename));
        }
      
        if (!file_exists($imagepath)) {
            file_put_contents($imagepath, base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII="));
        } else {
            $image = Image::load($imagepath)
                ->fit(Fit::Contain, $width, $height)
                ->optimize($this->optimizer)
                ->save();
        }
      
        $png = imagecreatefrompng($imagepath);
        imagesavealpha($png, true);

        return $png;
    }
}
