<?php

namespace App\Console\Commands;

use App\Enums\PartCategory;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\LDraw\Parse\Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class DeployUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lib:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the app after update deployments';

    /**
     * Execute the console command.
     */
    public function handle(Parser $parser): void
    {
        PartRelease::each(function (PartRelease $release) use ($parser){
            if (Storage::disk('local')->exists("upload/view/view{$release->short}")) {
                $release->clearMediaCollection('view');
                foreach(Storage::disk('local')->allFiles("upload/view/view{$release->short}") ?? [] as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) != 'png' && pathinfo($file, PATHINFO_EXTENSION) != 'gif') {
                        continue;
                    }
                    $dat_file = substr($file, 0, -3) . 'dat';
                    $mpd_file = substr($file, 0, -3) . 'mpd';
                    if (Storage::disk('local')->exists($mpd_file)) {
                        $text = $parser->formatText(Storage::disk('local')->get($mpd_file));
                        $text = explode("\n", $text);
                        array_shift($text);
                        $text = implode("\n", $text);
                    } else {
                        $text = $parser->formatText(Storage::disk('local')->get($dat_file));
                    }
    
                    $description = $parser->getDescription($text);
                    preg_match('#^\h*0\h+(File)?[Nn]ame:?\h+(?P<name>.*?)\h*$#um', $text, $matches);
                    $name = $name = Arr::get($matches, 'name');
    
                    if (Str::startsWith($description, '~Moved to')) {
                        $this->info("Skipped moved to part: {$file}, {$description}");
                        continue;
                    }
    
                    $part = Part::firstWhere('filename', "parts/{$name}") ?? Part::firstWhere('filename', 'parts/' . substr(basename($file), 0, -3) . 'dat');
                    
                    if (is_null($description) || $description == '') {
                        $description = $part->description;
                    }
                    if (is_null($name) || $name == '') {
                        $name = $part->name();
                    }
                    
                    $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
                    $newPath = $tempDir->path(basename($file, '.gif') . '.png'); 
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'gif') {
                        try {
                            $i = imagecreatefromgif(storage_path("app/{$file}"));
                        } catch (\Exception $e) {
                            $this->info("Invalid image, skipped: {$file}");
                            continue;
                        }
                        imagepng($i, $newPath);
                    } else {
                        copy(storage_path("app/{$file}"), $newPath);
                    }
                    
                    Image::load($newPath)
                        ->optimize()
                        ->save($newPath);
    
                    $release->addMedia($newPath)
                        ->withCustomProperties([
                            'description' => $description,
                            'filename' => $name,
                            'id' => $part->id,
                        ])
                        ->toMediaCollection('view');
                }
            }
        });
    }
}
