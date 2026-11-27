<?php

use App\Models\TrackerHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the tracker history chart with labelled status series', function () {
    TrackerHistory::create(['created_at' => '2026-03-01 16:00:00', 'history_data' => ['1' => 95, '2' => 561, '3' => 1462, '4' => 6126, '6' => 464]]);

    $response = $this->get(route('tracker.history'));

    $response->assertOk()
        ->assertSee('2026-03-01')
        ->assertSee('Needs More Votes')
        ->assertDontSee('Uncertified');
});
