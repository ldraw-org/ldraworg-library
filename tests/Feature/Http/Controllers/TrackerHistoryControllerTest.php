<?php

use App\Models\TrackerHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the tracker history chart with labelled status series', function () {
    TrackerHistory::create(['created_at' => '2026-03-01 16:00:00', 'history_data' => ['certified' => 95, 'awaiting_admin_review' => 561, 'needs_1_more_vote' => 1462, 'needs_2_more_votes' => 6126, 'errors_found' => 464]]);

    $response = $this->get(route('tracker.history'));

    $response->assertOk()
        ->assertSee('2026-03-01')
        ->assertSee('Needs More Votes')
        ->assertSee('Uncertified Subfiles');
});
