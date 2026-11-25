<?php

use App\Models\Part\PartRelease;
use App\Services\Part\Release\InstallerScript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->template = storage_path('framework/testing/installer/LDrawParts.iss');
    File::ensureDirectoryExists(dirname($this->template));
    file_put_contents(
        $this->template,
        "AppId={{@APP_ID@}\nAppVersion=@RELEASE_NAME@\nOutputBaseFilename=LDraw@RELEASE_SHORT@\nDefaultDirName={autopf}\\LDraw\n"
    );
    config(['ldraw.installer_template' => $this->template, 'ldraw.installer_app_id' => '8C0E9A61-7D3B-4B4E-9F2A-1C5D6E7F8A9B']);
    $this->release = PartRelease::factory()->create(['name' => '2026-12', 'short' => '2612']);
});

it('fills in the release and AppId outside the public library', function () {
    $written = app(InstallerScript::class)->write($this->release);

    expect($written)->toBeTrue()
        ->and(Storage::get(InstallerScript::PATH))->toBe(
            "AppId={{8C0E9A61-7D3B-4B4E-9F2A-1C5D6E7F8A9B}\nAppVersion=2026-12\nOutputBaseFilename=LDraw2612\nDefaultDirName={autopf}\\LDraw\n"
        )
        ->and(InstallerScript::PATH)->not->toStartWith('library/');
});

it('skips the script without an AppId', function () {
    config(['ldraw.installer_app_id' => null]);
    Log::spy();

    $written = app(InstallerScript::class)->write($this->release);

    expect($written)->toBeFalse();
    Storage::assertMissing(InstallerScript::PATH);
    Log::shouldHaveReceived('warning')->once();
});

it('skips the script without a template', function () {
    config(['ldraw.installer_template' => storage_path('framework/testing/installer/missing.iss')]);

    $written = app(InstallerScript::class)->write($this->release);

    expect($written)->toBeFalse();
    Storage::assertMissing(InstallerScript::PATH);
});
