<?php

use App\Enums\PartStatus;
use App\Models\Part\Part;
use App\Models\TrackerHistory;
use App\Services\LibraryStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('maps tracker snapshots to daily series, skipping legacy duplicated counts', function () {
    TrackerHistory::create(['created_at' => '2010-06-01 19:00:00', 'history_data' => ['1' => '710', '2' => '513', '3' => '1080', '4' => '1266', '5' => '1266', '6' => '369']]);
    TrackerHistory::create(['created_at' => '2024-01-01 16:00:00', 'history_data' => ['1' => 31, '2' => 615, '3' => 6055, '5' => 399, '6' => 399]]);
    TrackerHistory::create(['created_at' => '2026-03-01 15:00:00', 'history_data' => ['1' => 90, '2' => 560, '3' => 1460, '4' => 6120, '6' => 460]]);
    TrackerHistory::create(['created_at' => '2026-03-01 16:00:00', 'history_data' => ['1' => 95, '2' => 561, '3' => 1462, '4' => 6126, '6' => 464]]);

    $history = app(LibraryStatisticsService::class)->trackerHistory();

    expect($history)->toBe([
        'dates' => ['2010-06-01', '2024-01-01', '2026-03-01'],
        'counts' => [
            PartStatus::ErrorsFound->name => [369, 399, 464],
            PartStatus::UncertifiedSubfiles->name => [1266, 0, 0],
            PartStatus::Needs1MoreVote->name => [1080, 6055, 7588],
            PartStatus::AwaitingAdminReview->name => [513, 615, 561],
            PartStatus::Certified->name => [710, 31, 95],
        ],
    ]);
});

it('records a snapshot of unofficial part counts and refreshes the cached history', function () {
    $statistics = app(LibraryStatisticsService::class);
    expect($statistics->trackerHistory()['dates'])->toBe([]);
    Part::factory()->count(2)->create(['part_status' => PartStatus::ErrorsFound]);
    Part::factory()->create(['part_status' => PartStatus::Certified]);
    Part::factory()->official()->create();

    $statistics->recordTrackerHistory();

    expect(TrackerHistory::sole()->history_data->getArrayCopy())
        ->toEqual([PartStatus::Certified->value => 1, PartStatus::ErrorsFound->value => 2])
        ->and($statistics->trackerHistory()['counts'][PartStatus::ErrorsFound->name])->toBe([2]);
});
