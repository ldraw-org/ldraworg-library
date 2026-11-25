<?php

use App\Enums\Permission;
use App\Enums\ReleaseStage;
use App\Jobs\BackupDatabaseOnly;
use App\Jobs\MakePartRelease;
use App\Jobs\SyncRebrickableData;
use App\Livewire\Release\Create;
use App\Livewire\Release\Progress;
use App\Models\Part\PartReleaseRun;
use App\Models\User;
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
