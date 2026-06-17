<?php

namespace App\Services\Part\Release;

use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Storage;

class AddViewImage
{
    public function handle(Part $part, PartRelease $release, string $stagingPath): void
    {
        $viewImagePath = "{$stagingPath}/view/" . substr($part->filename, 0, -4) . '.png';
        $partImagePath = $part->getFirstMediaPath('image');
        if (!file_exists($partImagePath)) {
            $partImagePath = asset('images/blank.png');
        }
        $partImage = file_get_contents($partImagePath);
        Storage::put($viewImagePath, $partImage);
        $release
            ->addMedia(Storage::path($viewImagePath), 'image')
            ->withCustomProperties([
                'description' => $part->description,
                'filename' => $part->filename,
                'id' => $part->id,
            ])
            ->toMediaCollection('view');
    }
}
