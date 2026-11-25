<?php

use App\Enums\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as PermissionModel;

uses(RefreshDatabase::class);

it('creates permissions the enum defines but the database lacks', function () {
    PermissionModel::findByName(Permission::PartReleaseViewProgress->value)->delete();

    $this->artisan('lib:update')
        ->expectsOutputToContain('Created permission release.progress.view')
        ->assertSuccessful();

    expect(PermissionModel::where('name', Permission::PartReleaseViewProgress->value)->exists())->toBeTrue()
        ->and(PermissionModel::count())->toBe(count(Permission::cases()));
});

it('reports permissions the enum no longer defines without deleting them', function () {
    PermissionModel::findOrCreate('retired.permission');

    $this->artisan('lib:update')
        ->expectsOutputToContain('Permission retired.permission is no longer defined')
        ->doesntExpectOutputToContain('Created permission')
        ->assertSuccessful();

    expect(PermissionModel::where('name', 'retired.permission')->exists())->toBeTrue();
});
