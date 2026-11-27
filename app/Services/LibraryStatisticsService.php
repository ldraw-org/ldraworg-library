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
     * Snapshots are keyed by PartStatus value. Those taken before Needs More Votes was split
     * (November 2025) use the 'needs_more_votes' key instead of the two Needs* statuses; all
     * three are combined into the Needs1MoreVote series.
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
            $days[substr($row->created_at, 0, 10)] = [
                PartStatus::ErrorsFound->name => $count(PartStatus::ErrorsFound),
                PartStatus::UncertifiedSubfiles->name => $count(PartStatus::UncertifiedSubfiles),
                PartStatus::Needs1MoreVote->name => (int) Arr::get($data, 'needs_more_votes', 0)
                    + $count(PartStatus::Needs1MoreVote)
                    + $count(PartStatus::Needs2MoreVotes),
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
