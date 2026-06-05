<?php

namespace App\Services\Render;

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
        $ldconfigPath = Storage::path('library/official/LDConfig.ldr');

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
            'VerifyLDrawDir' => '0',
            'SaveWidth' => $width * 2,
            'SaveHeight' => $height * 2,
            'SaveAlpha' => '1',
            'SaveZoomToFit' => '1',
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
            ->implode("\n");
        $iniPath = $tempDir->path("ldview.ini");
        file_put_contents($iniPath, "[General]\n{$cmdOptions}\n");

        // Run LDView
        $ldviewcmd = "ldview {$filename} -IniFile={$iniPath} -LDrawDir={$ldrawdir} -SaveSnapshot={$imagepath}";
        if ($this->debug) {
            Log::debug($ldviewcmd);
        }

        $result = Process::run($ldviewcmd);
        if ($this->debug) {
            Log::debug($result->output());
            Log::debug($result->errorOutput());
            Storage::put("debug/part.mpd", file_get_contents($filename));
            Storage::put("/debug/ldview.ini", file_get_contents($iniPath));
            Log::debug("ldview " . Storage::path('debug/part.mpd') . " -IniFile=" . Storage::path('debug/ldview.ini'). " -LDrawDir=" . Storage::path('debug/ldraw') . " -SaveSnapshot=" . Storage::path('debug/part.png'));
            if (file_exists($imagepath)) {
                Storage::put("/debug/part.png", file_get_contents($imagepath));
            } else {
                Log::debug('No image found');
            }
        }

        if (!file_exists($imagepath)) {
            file_put_contents($imagepath, base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII="));
        } else {
            Image::load($imagepath)
                ->fit(Fit::Contain, $width, $height)
                ->optimize($this->optimizer)
                ->save();
        }

        $png = imagecreatefrompng($imagepath);
        imagesavealpha($png, true);

        return $png;
    }
}
