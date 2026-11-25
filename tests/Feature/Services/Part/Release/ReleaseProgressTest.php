<?php

use App\Enums\ReleaseStage;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
use App\Services\Part\Release\PartReleaseManager;
use App\Services\Part\Release\ReleaseProgress;
use App\Settings\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('starts a run when none exists', function () {
    $actor = User::factory()->create();

    $run = app(ReleaseProgress::class)->start($actor);

    expect($run)
        ->stage->toBe(ReleaseStage::Queued)
        ->user_id->toBe($actor->id)
        ->isRunning()->toBeTrue();
});

it('refuses to start while a release is running', function () {
    $running = PartReleaseRun::factory()->create(['stage' => ReleaseStage::ReleasingParts]);

    $run = app(ReleaseProgress::class)->start(User::factory()->create());

    expect($run)->toBeNull()
        ->and($running->fresh()->stage)->toBe(ReleaseStage::ReleasingParts);
});

it('stays locked out after a release failed', function () {
    $failed = PartReleaseRun::factory()->failed()->create();

    $run = app(ReleaseProgress::class)->start(User::factory()->create());

    expect($run)->toBeNull()
        ->and(app(ReleaseProgress::class)->isLockedOut())->toBeTrue()
        ->and($failed->fresh()->error)->toBe('The zips could not be written');
});

it('replaces a completed run with a fresh one', function () {
    $completed = PartRelease::factory()->create();
    PartReleaseRun::factory()->complete()->create(['part_release_id' => $completed->id, 'progress_current' => 9, 'progress_total' => 9]);
    $actor = User::factory()->create();

    $run = app(ReleaseProgress::class)->start($actor);

    expect($run)
        ->stage->toBe(ReleaseStage::Queued)
        ->user_id->toBe($actor->id)
        ->part_release_id->toBeNull()
        ->progress_current->toBeNull()
        ->progress_total->toBeNull();
    expect(PartReleaseRun::count())->toBe(1);
});

it('marks the run failed and stops the chain when a step fails', function () {
    Http::preventStrayRequests();
    config(['ldraw.rebrickable_csv_url' => 'https://downloads.test/all_downloads.tar.gz']);
    Http::fake(['downloads.test/*' => Http::response('', 500)]);
    fakeRebrickableCsv([]);

    expect(fn () => app(PartReleaseManager::class)->queueRelease(User::factory()->create()))
        ->toThrow(RuntimeException::class);

    expect(app(ReleaseProgress::class)->current())
        ->stage->toBe(ReleaseStage::SyncingRebrickable)
        ->error->toBe('Downloading the Rebrickable CSVs failed with HTTP 500');
    expect(PartRelease::count())->toBe(0)
        ->and(app(LibrarySettings::class)->tracker_locked)->toBeFalse();
});
