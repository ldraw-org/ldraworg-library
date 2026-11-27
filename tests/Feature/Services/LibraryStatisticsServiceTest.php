<?php

use App\Enums\PartStatus;
use App\Models\Part\Part;
use App\Models\TrackerHistory;
use App\Services\LibraryStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('maps tracker snapshots to daily series, combining all needs votes counts', function () {
    TrackerHistory::create(['created_at' => '2010-06-01 19:00:00', 'history_data' => ['certified' => 710, 'awaiting_admin_review' => 513, 'needs_more_votes' => 1080, 'uncertified_subfiles' => 1266, 'errors_found' => 369]]);
    TrackerHistory::create(['created_at' => '2024-01-01 16:00:00', 'history_data' => ['certified' => 31, 'awaiting_admin_review' => 615, 'needs_more_votes' => 6055, 'errors_found' => 399]]);
    TrackerHistory::create(['created_at' => '2026-03-01 15:00:00', 'history_data' => ['certified' => 90, 'awaiting_admin_review' => 560, 'needs_1_more_vote' => 1460, 'needs_2_more_votes' => 6120, 'errors_found' => 460]]);
    TrackerHistory::create(['created_at' => '2026-03-01 16:00:00', 'history_data' => ['certified' => 95, 'awaiting_admin_review' => 561, 'needs_1_more_vote' => 1462, 'needs_2_more_votes' => 6126, 'errors_found' => 464]]);

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
        ->toEqual(['certified' => 1, 'errors_found' => 2])
        ->and($statistics->trackerHistory()['counts'][PartStatus::ErrorsFound->name])->toBe([2]);
});
