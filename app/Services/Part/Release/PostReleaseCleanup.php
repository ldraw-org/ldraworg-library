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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostReleaseCleanup
{
    protected const int REFRESH_CHUNK = 500;

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

        // A subquery rather than a list of ids, which would near MySQL's 65,535 placeholder limit as the library grows
        $officialIds = Part::official()->select('id');
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
        foreach (array_chunk($this->affectedPartIds($release), self::REFRESH_CHUNK) as $ids) {
            Part::whereIn('id', $ids)->each(function (Part $part): void {
                GeneratePartImage::dispatch($part->withoutRelations())->onQueue('maintenance');
                CheckPart::dispatch($part->withoutRelations())->onQueue('maintenance');
                $this->subpartSync->loadSubparts($part);
            });
        }
    }

    /**
     * The ids of the released parts and every part using them, directly or not.
     * Found in the database with a distinct recursive query: loading each released part's ancestors as models
     * hydrates one per path, ~79,000 for a fix to a widely used primitive like 4-4edge.dat, enough to exhaust memory.
     *
     * @return list<int>
     */
    protected function affectedPartIds(PartRelease $release): array
    {
        $released = DB::table('parts')->select('id')->where('part_release_id', $release->id);
        $users = DB::table('related_parts')
            ->join('affected_parts', 'affected_parts.id', '=', 'related_parts.subpart_id')
            ->select('related_parts.parent_id');

        return (new Part())->newQuery()->getQuery()
            ->withRecursiveExpression('affected_parts', $released->union($users), ['id'])
            ->from('affected_parts')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
