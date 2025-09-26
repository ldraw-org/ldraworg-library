<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImage;
use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
    public function handle(): void
    {
        OmrModel::lazy()
            ->each(function (OmrModel $model) {
                $model->clearMediaCollection('image');
                $model->clearMediaCollection('file');
                $model->addMediaFromDisk("omr/{$model->filename()}", 'library')
                    ->preservingOriginal()
                    ->toMediaCollection('file');
                if (Storage::disk('images')->exists('omr/models/' . substr($model->filename(), 0, -4) . '.png')) {    
                    $model->addMediaFromDisk('omr/models/' . substr($model->filename(), 0, -4) . '.png', 'images')
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                } else {
                    UpdateImage::dispatch($model);
                }
            });
        PartRelease::whereNotNull('part_list')
            ->each(function (PartRelease $release) {
                $release->clearMediaCollection('view');
                foreach($release->part_list as list($description, $filename)) {
                    $release->addMediaFromDisk('library/updates/view' . $release->short . '/' . substr($filename, 0, -4) . '.png', 'images')
                        ->preservingOriginal()
                        ->withCustomProperties([
                            'description' => $description,
                            'filename' => $filename,
                            'id' => Part::official()->where('filename', $filename)->first()?->id,
                        ])
                        ->toMediaCollection('view');
                }
            });
        Part::lazy()
            ->each(function (Part $part) {
                $part->clearMediaCollection('image');
                if (Storage::disk('images')->exists("library/{$part->imagePath()}")) {
                    $part->addMediaFromDisk("library/{$part->imagePath()}", 'images')
                        ->preservingOriginal()
                        ->toMediaCollection('image');
                } else {
                    UpdateImage::dispatch($part);
                }
            });
    }
}
