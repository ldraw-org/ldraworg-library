<?php

namespace App\LDraw\Render;

use App\LDraw\LDrawModelMaker;
use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LDView
{
    protected readonly bool $debug;

    public function __construct(
        protected LibrarySettings $settings,
        protected LDrawModelMaker $modelmaker,
    ) {
        $this->debug = config('ldraw.ldview_debug', false);
    }

    public function render(Part|OmrModel $part): GDImage
    {
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $ldconfigPath = Storage::disk('library')->path('official/LDConfig.ldr');
        // LDview requires a p and parts directory
        $ldrawdir = $tempDir->path("ldraw");
        $tempDir->path("ldraw/parts");
        $tempDir->path("ldraw/p");

        // Store the part as an mpd
        $filename = $tempDir->path("part.mpd");
        if ($part instanceof Part) {
            if (array_key_exists(basename($part->filename, '.dat'), $this->settings->default_render_views)) {
                $matrix = $this->settings->default_render_views[basename($part->filename, '.dat')];
            } elseif (array_key_exists(basename($part?->base_part->filename ?? '', '.dat'), $this->settings->default_render_views)) {
                $matrix = $this->settings->default_render_views[basename($part->base_part->filename, '.dat')];
            } else {
                $matrix = '1 0 0 0 1 0 0 0 1';
            }
        } else {
            $matrix = "1 0 0 0 1 0 0 0 1";
        }

        if ($part instanceof Part) {
            file_put_contents($filename, $this->modelmaker->partMpd($part, $matrix));
        } else {
            file_put_contents($filename, $this->modelmaker->modelMpd($part));
        }

        if ($part instanceof Part) {
            $normal_size = "-SaveWidth={$this->settings->max_part_render_width} -SaveHeight={$this->settings->max_part_render_height}";
        } else {
            $normal_size = "-SaveWidth={$this->settings->max_model_render_width} -SaveHeight={$this->settings->max_model_render_height}";
        }
        $imagepath = $tempDir->path("part.png");

        // Make the ldview.ini
        $cmds = ['[General]'];
        foreach ($this->settings->ldview_options as $command => $value) {
            $cmds[] = "{$command}={$value}";
        }

        $inipath = $tempDir->path("ldview.ini");
        file_put_contents($inipath, implode("\n", $cmds));

        // Run LDView
        $ldviewcmd = "ldview {$filename} -LDConfig={$ldconfigPath} -LDrawDir={$ldrawdir} -IniFile={$inipath} {$normal_size} -SaveSnapshot={$imagepath}";
        if ($this->debug) {
            Log::debug($ldviewcmd);
        }

        $result = Process::run($ldviewcmd);
        if ($this->debug) {
            Log::debug($result->output());
            Log::debug($result->errorOutput());
            Storage::put("/db/part.mpd", file_get_contents($filename));
            Storage::put("/db/ldview.ini", file_get_contents($inipath));
        }
        $png = imagecreatefrompng($imagepath);
        imagesavealpha($png, true);

        return $png;
    }
}
