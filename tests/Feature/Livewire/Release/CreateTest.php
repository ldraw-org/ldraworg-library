<?php

use App\Enums\Permission;
use App\Jobs\BackupDatabaseOnly;
use App\Jobs\MakePartRelease;
use App\Jobs\SyncRebrickableData;
use App\Livewire\Release\Create;
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

    Bus::assertChained([
        fn (SyncRebrickableData $job): bool => !$job->forceDownload && !$job->skipDownload,
        BackupDatabaseOnly::class,
        fn (MakePartRelease $job): bool => $job->user->is($releaser) && $job->includeLdconfig && $job->extraFiles === [],
    ]);
});
