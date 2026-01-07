<?php

namespace App\Services\Render;

use App\Services\LDraw\LDrawModelMaker;
use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Settings\LibrarySettings;
use App\Jobs\UpdateImage;
use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class LDView
{
    protected readonly bool $debug;
    protected string $ldconfigPath;

    protected float $asyncTimeout = 0.25;
    protected int $asyncSleepInterval = 50000;
  
    public function __construct(
        protected LibrarySettings $settings,
        protected LDrawModelMaker $modelmaker,
    ) {
        $this->debug = config('ldraw.ldview_debug', false);
        $this->ldconfigPath = Storage::disk('library')->path('official/LDConfig.ldr');
    }

    public function renderHybrid(string|Part|OmrModel $part): ?GDImage
    {
        $filename = Str::uuid();
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
      
        $filePath = $this->setupAndSaveFile($tempDir, $filename, $part);
        [$width, $height] = $this->getDimensions($part);
        $imagePath = $tempDir->path("{$filename}.png");
      
        $commandArray = $this->getCommandArray($filePath, $imagePath, $tempDir->path('ldraw'), $tempDir->path('ldview.ini'), $width * 2, $height * 2);
      
        // Run LDView
        $process = Process::command($commandArray)->start();
        $startTime = microtime(true);

        while ($process->running() && (microtime(true) - $startTime) < $this->asyncTimeout) {
            usleep($this->asyncSleepInterval);
        }

        if (!$process->running()) {
            $result = $process->wait(); // Wait for any remaining output
            $this->log($result, $filePath, $imagePath, $commandArray);
            return $this->processImage($imagePath, $width, $height);
        }
        if ($process->running()) {
            $process->stop(0);
        }
        UpdateImage::dispatch($part);
        return null;
    }

    public function renderSync(string|Part|OmrModel $part): ?GDImage
    {
        $filename = Str::uuid();
        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
      
        $filePath = $this->setupAndSaveFile($tempDir, $filename, $part);
        [$width, $height] = $this->getDimensions($part);
        $imagePath = $tempDir->path("{$filename}.png");
      
        $commandArray = $this->getCommandArray($filePath, $imagePath, $tempDir->path('ldraw'), $tempDir->path('ldview.ini'), $width * 2, $height * 2);

        // Run LDview
        $result = Process::command($commandArray)->run();
        $this->log($result, $filePath, $imagePath, $commandArray);
        return $this->processImage($imagePath, $width, $height);
    
    }
  
    protected function getDimensions(string|Part|OmrModel $part): array
    {
        $isPart = $part instanceof Part;
        $width = $isPart ? $this->settings->max_part_render_width : $this->settings->max_model_render_width;
        $height = $isPart ? $this->settings->max_part_render_height : $this->settings->max_model_render_height;
        
        return [$width, $height];
    }
  
    protected function log($result, string $filePath, string $imagePath, array $commandArray): void
    {
        $renderSucceeded = file_exists($imagePath);

        if ($result->failed() || $this->debug) {
            $logData = [
                'success_by_file' => $renderSucceeded,
                'exitCode' => $result->exitCode(),
                'command' => implode(' ', $commandArray),
                'output' => $result->output(),
                'errorOutput' => $result->errorOutput(),
            ];
            
            if ($result->failed() && !$renderSucceeded) {
                 Log::error('LDView render failed.', $logData);
            } elseif ($this->debug) {
                 Log::debug('LDView render details.', $logData);
            }
            
            if ($this->debug && file_exists($filePath)) {
                 $file = basename($filePath);
                 Storage::put("/debug/{$file}", file_get_contents($filePath));
            }
        }
    }

    protected function setupAndSaveFile(TemporaryDirectory $tempDir, string $filename, string|Part|OmrModel $part): string
    {  
        // LDview requires p and parts directory
        $ldrawPath = $tempDir->path('ldraw');
        File::ensureDirectoryExists("{$ldrawPath}/parts", 0755, true); 
        File::ensureDirectoryExists("{$ldrawPath}/p", 0755, true);

        // LDView requires an INI file
        $iniPath = $tempDir->path('ldview.ini');
        file_put_contents($iniPath, "[General]\n");
        
        // Store the mpd
        $isPart = $part instanceof Part;
        $datFile = $tempDir->path("{$filename}.mpd");
        $contents = $isPart ? $this->modelmaker->partMpd($part) : $this->modelmaker->modelMpd($part);
        file_put_contents($datFile, $contents);

        return $datFile;
    }
  
    protected function getCommandArray(string $filePath, string $imagePath, string $ldrawPath, string $iniPath, int $width, int $height): array
    {
        $options = [
            'ProcessLDConfig' => '1',
            'LDConfig' => $this->ldconfigPath,
            'LDrawDir' => $ldrawPath,
            'IniFile' => $iniPath,
            'SaveWidth' => $width,
            'SaveHeight' => $height,
            'SaveAlpha' => '1',
            'SaveZoomToFit' => '1',
            'SaveSnapshot' => $imagePath,
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
        $commandArray = [
            'ldview', 
            $filePath
        ];
        foreach ($options as $command => $value) {
            $commandArray[] = "-{$command}=" . strval($value);
        } 
        return $commandArray;
    }
  
    protected function processImage(string $imagePath, int $width, int $height): ?GDImage
    {
        if (file_exists($imagePath)) {
            $image = Image::load($imagePath)
                ->fit(Fit::Contain, $width, $height)
                ->optimize()
                ->save();          
            $png = imagecreatefrompng($imagePath);
            imagesavealpha($png, true);

            return $png;
        }
        return null;
    }
}
