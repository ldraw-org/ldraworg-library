<?php

namespace App\Console\Commands;

use App\Enums\PreviewRotation;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\LDraw\ZipFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    public function handle(ZipFiles $zipFiles): void
    {
        Part::cursor()
            ->each(function (Part $part) {
                $preview = $part->getRawOriginal('preview');
                if ($preview === null) {
                    $previewRotation = PreviewRotation::Default;
                } else {
                    $previewText = Str::after($preview, '16 0 0 0 ');
                    $previewRotation = PreviewRotation::tryFrom($previewText);
                    if ($previewRotation === null) {
                        $this->info("Part {$part->filename}: Reverted preview to default");
                        $previewRotation = PreviewRotation::Default;
                    }
                }
                $part->preview = $previewRotation;
                $part->save();
            });
    }
}
