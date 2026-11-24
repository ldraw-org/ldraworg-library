<?php

namespace App\Services\Part\Release;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Storage;

class AddViewImage
{
    /**
     * Copy the part's current image into the release's "view" media collection.
     * Must run before the part's own image collection is cleared.
     */
    public function handle(Part $part, PartRelease $release, string $stagingFolder): void
    {
        $viewImagePath = "{$stagingFolder}/view/" . substr($part->filename, 0, -4) . '.png';
        $partImagePath = $part->getFirstMediaPath('image');
        if (!file_exists($partImagePath)) {
            $partImagePath = public_path('images/blank.png');
        }
        Storage::put($viewImagePath, file_get_contents($partImagePath));

        $release
            ->addMedia(Storage::path($viewImagePath), 'image')
            ->withCustomProperties([
                'description' => $part->description,
                'filename' => $part->filename,
                'id' => $part->id,
                'highlight' => $part->release_highlight,
            ])
            ->toMediaCollection('view');
    }
}
