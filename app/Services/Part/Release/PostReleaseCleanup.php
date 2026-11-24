<?php

namespace App\Services\Part\Release;

use App\Enums\PartStatus;
use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\Vote;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use App\Services\Part\SyncSubparts;
use App\Services\Support\ZipFiles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostReleaseCleanup
{
    public function __construct(
        protected ZipFiles $zipFiles,
        protected SyncSubparts $subpartSync,
        protected CacheService $cache,
    ) {
    }

    public function handle(PartRelease $release): void
    {
        $this->resetOfficialParts();
        $this->remarkUnofficialParts();

        Storage::delete('library/unofficial/ldrawunf.zip');
        $this->zipFiles->unofficialZip(Part::unofficial()->first());

        $this->refreshAffectedParts($release);

        foreach ([CacheKey::OfficialPartCount, CacheKey::PartReleaseCurrent] as $key) {
            $this->cache->reset($key);
            $this->cache->warm($key);
        }
    }

    /**
     * Clear review, flag and notification state from every official part.
     */
    protected function resetOfficialParts(): void
    {
        Part::official()->update([
            'part_status' => PartStatus::Official,
            'delete_flag' => 0,
            'has_minor_edit' => false,
            'missing_parts' => null,
            'manual_hold_flag' => 0,
            'marked_for_release' => false,
            'release_highlight' => false,
        ]);

        $officialIds = Part::official()->pluck('id');
        Vote::whereIn('part_id', $officialIds)->delete();
        DB::table('user_part_notifications')->whereIn('part_id', $officialIds)->delete();
    }

    /**
     * Re-evaluate which unofficial parts are marked for the next release.
     */
    protected function remarkUnofficialParts(): void
    {
        Part::unofficial()
            ->where('part_status', PartStatus::Certified)
            ->where('can_release', true)
            ->update(['marked_for_release' => true]);
        Part::unofficial()
            ->where('can_release', false)
            ->where('marked_for_release', true)
            ->update(['marked_for_release' => false]);
    }

    /**
     * Re-render, recheck and resync the released parts and everything that uses them.
     */
    protected function refreshAffectedParts(PartRelease $release): void
    {
        $affectedIds = [];
        $release->parts()->with('ancestorsAndSelf')->each(function (Part $part) use (&$affectedIds): void {
            $affectedIds = array_merge($affectedIds, $part->ancestorsAndSelf->pluck('id')->all());
        });

        Part::whereIn('id', array_unique($affectedIds))->chunkById(500, function (Collection $parts): void {
            $parts->each(function (Part $part): void {
                GeneratePartImage::dispatch($part->withoutRelations())->onQueue('maintenance');
                CheckPart::dispatch($part->withoutRelations())->onQueue('maintenance');
                $this->subpartSync->loadSubparts($part);
            });
        });
    }
}
