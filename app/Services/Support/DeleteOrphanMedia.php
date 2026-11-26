<?php

namespace App\Services\Support;

use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteOrphanMedia
{
    /**
     * @var list<class-string<\Spatie\MediaLibrary\HasMedia>>
     */
    private const array MEDIA_MODELS = [
        Part::class,
        PartRelease::class,
        OmrModel::class,
    ];

    /**
     * Delete media, with its files, whose owning model no longer exists.
     * Global scopes are ignored so media of a scoped-out model is never treated as orphaned.
     *
     * @return int The number of media items deleted
     */
    public function handle(): int
    {
        $deleted = 0;

        Media::whereDoesntHaveMorph('model', self::MEDIA_MODELS, fn (Builder $query) => $query->withoutGlobalScopes())
            ->lazyById()
            ->each(function (Media $media) use (&$deleted): void {
                $media->delete();
                $deleted++;
            });

        if ($deleted > 0) {
            Log::warning('Deleted orphaned media', ['count' => $deleted]);
        }

        return $deleted;
    }
}
