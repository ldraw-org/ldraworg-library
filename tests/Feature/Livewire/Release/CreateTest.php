<?php

use App\Enums\Permission;
use App\Enums\ReleaseStage;
use App\Jobs\BackupDatabaseOnly;
use App\Jobs\MakePartRelease;
use App\Jobs\SyncRebrickableData;
use App\Livewire\Release\Create;
use App\Livewire\Release\Progress;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
use App\Services\Part\Release\ReleaseProgress;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('syncs Rebrickable data and backs up the database before making the release', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    Bus::fake();

    Livewire::test(Create::class)
        ->callAction(TestAction::make('create-release')->table(), ['include-ldconfig' => true, 'additional-files' => null])
        ->assertRedirectToRoute('tracker.activity');

    $run = PartReleaseRun::sole();
    expect($run)->stage->toBe(ReleaseStage::Queued)->user_id->toBe($releaser->id);
    Bus::assertChained([
        fn (SyncRebrickableData $job): bool => !$job->forceDownload && !$job->skipDownload && $job->releaseRun->is($run),
        fn (BackupDatabaseOnly $job): bool => $job->releaseRun->is($run),
        fn (MakePartRelease $job): bool => $job->user->is($releaser) && $job->includeLdconfig && $job->extraFiles === [] && $job->releaseRun->is($run),
    ]);
});

it('hides the release actions while a release is running', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    PartReleaseRun::factory()->create(['stage' => ReleaseStage::ReleasingParts, 'progress_current' => 3, 'progress_total' => 10]);
    Bus::fake();

    Livewire::test(Create::class)
        ->assertActionHidden(TestAction::make('create-release')->table())
        ->assertActionHidden(TestAction::make('reset-marked-parts')->table())
        ->assertSeeLivewire(Progress::class);

    Bus::assertNothingDispatched();
});

it('keeps the release actions hidden after a release failed', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    PartReleaseRun::factory()->failed()->create();

    Livewire::test(Create::class)
        ->assertActionHidden(TestAction::make('create-release')->table())
        ->assertActionHidden(TestAction::make('reset-marked-parts')->table());
    Livewire::test(Progress::class)
        ->assertDontSeeHtml('wire:poll')
        ->assertSee('Making and publishing the zips failed')
        ->assertSee('New releases are locked out until the database is rolled back.');
});

it('shows release admins the milestones of the running release', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    PartReleaseRun::factory()->create(['stage' => ReleaseStage::ReleasingParts, 'progress_current' => 3, 'progress_total' => 10]);

    Livewire::test(Progress::class)
        ->assertSeeHtml('wire:poll.5s.visible')
        ->assertSee('Parts release in progress')
        ->assertSeeInOrder(['Releasing parts', '(3 of 10)', 'Making and publishing the zips']);
});

it('keeps the release milestones from other users', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Progress::class)->assertForbidden();
});

it('tells everyone on the tracker that a release is running', function () {
    PartReleaseRun::factory()->create(['stage' => ReleaseStage::BackingUp]);

    $this->get(route('tracker.activity'))
        ->assertOk()
        ->assertSee('A Parts Library update is in progress');
});

it('collapses a completed release to a line its viewer can dismiss', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    $release = PartRelease::factory()->create(['name' => '2026-12']);
    $run = PartReleaseRun::factory()->complete()->create(['part_release_id' => $release->id]);

    Livewire::test(Progress::class)
        ->assertSee('Parts release 2026-12 completed')
        ->assertDontSee('Making and publishing the zips')
        ->assertDontSeeHtml('wire:poll')
        ->call('dismiss')
        ->assertDontSee('Parts release 2026-12 completed');
    Livewire::test(Progress::class)->assertDontSee('Parts release 2026-12 completed');

    $this->travel(1)->minutes();
    $run->update(['created_at' => now()]);

    Livewire::test(Progress::class)->assertSee('Parts release 2026-12 completed');
});

it('lets a Super Admin clear the lock-out left by a failed release', function () {
    $this->actingAs(User::factory()->create()->assignRole('Super Admin'));
    PartReleaseRun::factory()->failed()->create();

    Livewire::test(Progress::class)
        ->assertSee('Clear release lock-out')
        ->call('clearLockout')
        ->assertDontSee('Clear release lock-out');

    expect(PartReleaseRun::count())->toBe(0)
        ->and(app(ReleaseProgress::class)->isLockedOut())->toBeFalse();
});

it('keeps the lock-out clearing from release admins who are not Super Admins', function () {
    $releaser = User::factory()->create();
    $releaser->givePermissionTo(Permission::PartReleaseCreate->value);
    $this->actingAs($releaser);
    PartReleaseRun::factory()->failed()->create();

    Livewire::test(Progress::class)
        ->assertDontSee('Clear release lock-out')
        ->call('clearLockout')
        ->assertForbidden();

    expect(PartReleaseRun::count())->toBe(1);
});

it('lets a stalled release be cleared but not one still making progress', function () {
    $this->actingAs(User::factory()->create()->assignRole('Super Admin'));
    $run = PartReleaseRun::factory()->create(['stage' => ReleaseStage::MakingZips]);

    Livewire::test(Progress::class)
        ->assertDontSee('Clear release lock-out')
        ->call('clearLockout');
    expect($run->fresh())->not->toBeNull();

    $this->travel(PartReleaseRun::STALLED_AFTER_MINUTES + 1)->minutes();

    Livewire::test(Progress::class)
        ->assertSee('No progress for over 2 hours')
        ->call('clearLockout');
    expect($run->fresh())->toBeNull();
});
