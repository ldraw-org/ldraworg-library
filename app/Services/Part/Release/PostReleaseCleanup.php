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

    /**
     * @param list<int> $fixedIds the official parts updated by a released fix
     */
    public function handle(PartRelease $release, array $fixedIds = []): void
    {
        $this->resetOfficialParts();
        $this->remarkUnofficialParts();

        Storage::delete('library/unofficial/ldrawunf.zip');
        $this->zipFiles->unofficialZip(Part::unofficial()->first());

        $this->refreshAffectedParts($release, $fixedIds);

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
     * Re-render, recheck and resync every released part, and every part using a fixed one.
     *
     * @param list<int> $fixedIds
     */
    protected function refreshAffectedParts(PartRelease $release, array $fixedIds): void
    {
        $ids = Part::where('part_release_id', $release->id)
            ->pluck('id')
            ->merge($this->partIdsUsing($fixedIds))
            ->unique()
            ->all();

        foreach (array_chunk($ids, self::REFRESH_CHUNK) as $chunk) {
            Part::whereIn('id', $chunk)->each(function (Part $part): void {
                GeneratePartImage::dispatch($part->withoutRelations())->onQueue('maintenance');
                CheckPart::dispatch($part->withoutRelations())->onQueue('maintenance');
                $this->subpartSync->loadSubparts($part);
            });
        }
    }

    /**
     * The ids of every part using the given parts, directly or not.
     * Only fixes can change what their users look like: a minor edit changes just the header, and a new part's
     * users were already rendered with it while it was unofficial. Any fix counts, as comparing geometry is not
     * worth it for the few fixes in a release.
     * Found in the database with a distinct recursive query: loading ancestors as models hydrates one per path,
     * ~79,000 for a fix to a widely used primitive like 4-4edge.dat, enough to exhaust memory.
     *
     * @param list<int> $partIds
     * @return list<int>
     */
    protected function partIdsUsing(array $partIds): array
    {
        if ($partIds === []) {
            return [];
        }

        $parents = DB::table('related_parts')->select('parent_id')->whereIn('subpart_id', $partIds);
        $users = DB::table('related_parts')
            ->join('using_parts', 'using_parts.id', '=', 'related_parts.subpart_id')
            ->select('related_parts.parent_id');

        return (new Part())->newQuery()->getQuery()
            ->withRecursiveExpression('using_parts', $parents->union($users), ['id'])
            ->from('using_parts')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
