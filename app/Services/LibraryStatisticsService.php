<?php

namespace App\Services;

use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\TrackerHistory;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use Illuminate\Support\Arr;

class LibraryStatisticsService
{
    public function __construct(
        protected CacheService $cache
    ) {
    }

    public function officialPartCount(bool $live = false): int
    {
        if ($live) {
            return $this->computeOfficialPartCount();
        }

        return $this->cache->remember(
            CacheKey::OfficialPartCount,
            fn () => $this->computeOfficialPartCount()
        );
    }
    private function computeOfficialPartCount(): int
    {
        return Part::official()
            ->where('type', PartType::Part)
            ->whereNull('type_qualifier')
            ->whereNotLike('filename', 'parts/t%.dat')
            ->activeParts()
            ->count();
    }

    public function recordTrackerHistory(): void
    {
        TrackerHistory::create([
            'history_data' => Part::query()
                ->unofficial()
                ->selectRaw('part_status, count(*) as count')
                ->groupBy('part_status')
                ->pluck('count', 'part_status')
                ->all(),
        ]);

        $this->cache->reset(CacheKey::TrackerHistory);
    }

    /**
     * Daily unofficial part counts per chart series, oldest first, one entry per day
     * (the last snapshot of a day wins).
     *
     * Needs1MoreVote and Needs2MoreVotes are combined into the Needs1MoreVote series.
     *
     * Snapshots taken before the November 2025 status renumbering used 3 = NeedsMoreVotes,
     * 4 = UncertifiedSubfiles (until June 2023) and 5 = ErrorsFound. The renumbering copied
     * 5 to 6 and 4 to 5 without removing the originals, so those rows always contain key 5
     * and keys 4 and 5 hold stale copies.
     *
     * @return array{dates: list<string>, counts: array<string, list<int>>}
     */
    public function trackerHistory(): array
    {
        return $this->cache->remember(
            CacheKey::TrackerHistory,
            fn (): array => $this->computeTrackerHistory()
        );
    }

    /**
     * @return array{dates: list<string>, counts: array<string, list<int>>}
     */
    private function computeTrackerHistory(): array
    {
        $days = [];
        foreach (TrackerHistory::query()->oldest()->oldest('id')->toBase()->cursor() as $row) {
            $data = json_decode($row->history_data, true);
            $count = fn (PartStatus $status): int => (int) Arr::get($data, $status->value, 0);
            $beforeRenumbering = Arr::has($data, PartStatus::UncertifiedSubfiles->value);
            $days[substr($row->created_at, 0, 10)] = [
                PartStatus::ErrorsFound->name => $count(PartStatus::ErrorsFound),
                PartStatus::UncertifiedSubfiles->name => $beforeRenumbering
                    ? $count(PartStatus::Needs2MoreVotes)
                    : $count(PartStatus::UncertifiedSubfiles),
                PartStatus::Needs1MoreVote->name => $beforeRenumbering
                    ? $count(PartStatus::Needs1MoreVote)
                    : $count(PartStatus::Needs1MoreVote) + $count(PartStatus::Needs2MoreVotes),
                PartStatus::AwaitingAdminReview->name => $count(PartStatus::AwaitingAdminReview),
                PartStatus::Certified->name => $count(PartStatus::Certified),
            ];
        }

        $counts = [];
        foreach (array_keys(reset($days) ?: []) as $series) {
            $counts[$series] = array_column($days, $series);
        }

        return [
            'dates' => array_map(strval(...), array_keys($days)),
            'counts' => $counts,
        ];
    }
}
