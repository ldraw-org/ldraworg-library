<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReleaseImage;
use App\Models\Part\PartRelease;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lib:render-release-images')]
#[Description('Command description')]
class RenderReleaseImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        PartRelease::each(
            fn (PartRelease $release) => GenerateReleaseImage::dispatch($release)
        );
    }
}
