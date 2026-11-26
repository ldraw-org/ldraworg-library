<?php

use App\Enums\Permission;
use App\Livewire\Part\Submit;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Check\Enums\PartError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function submitter(): User
{
    $user = User::factory()->create(['ca_confirm' => true]);
    $user->givePermissionTo(Permission::PartSubmitRegular->value);

    return $user;
}

function texmapUpload(string $name = 'texture.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, uncompressedTexmapPng());
}

function invalidPartUpload(string $name = 'bad.dat'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "0 Not an LDraw part\r\n1 16 garbage\r\n");
}

/**
 * @param  array<string, array{filename: string, hasErrors: bool}>  $fileStates
 * @return array<string, bool> whether each file has errors, keyed by filename
 */
function errorsByFilename(array $fileStates): array
{
    return collect($fileStates)->mapWithKeys(fn (array $state): array => [$state['filename'] => $state['hasErrors']])->all();
}

it('forbids users who have not confirmed the contributor agreement', function () {
    $user = User::factory()->create(['ca_confirm' => false]);
    $user->givePermissionTo(Permission::PartSubmitRegular->value);

    Livewire::actingAs($user)
        ->test(Submit::class)
        ->assertForbidden();
});

it('validates files as they are uploaded and shows only those with messages', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload(), invalidPartUpload()]])
        ->assertSee('bad.dat')
        ->assertDontSee('texture.png');

    expect(errorsByFilename($component->get('fileStates')))->toBe(['texture.png' => false, 'bad.dat' => true]);
});

it('tracks uploads that share a filename separately', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload('texture.png'), invalidPartUpload('texture.png')]]);

    expect(collect($component->get('fileStates'))->pluck('hasErrors')->all())->toBe([false, true]);
});

it('forgets the validation state of a removed upload', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload(), invalidPartUpload()]]);

    $badKey = collect($component->get('fileStates'))->search(fn (array $state): bool => $state['filename'] === 'bad.dat');

    $component
        ->call('callSchemaComponentMethod', 'form.partfiles', 'removeUploadedFile', ['fileKey' => $badKey])
        ->assertDontSee('bad.dat');

    expect(errorsByFilename($component->get('fileStates')))->toBe(['texture.png' => false]);
});

it('submits a valid file as the signed in user', function () {
    $user = submitter();

    Livewire::actingAs($user)
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload()]])
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('rejected_files', '')
        ->assertCount('submitted_parts', 1)
        ->assertDispatched('open-modal', id: 'post-submit');

    expect(Part::sole())
        ->filename->toBe('parts/textures/texture.png')
        ->user_id->toBe($user->id);
});

it('submits only the files that pass validation and lists the rest as rejected', function () {
    Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload(), invalidPartUpload()]])
        ->call('create')
        ->assertSet('rejected_files', 'bad.dat')
        ->assertCount('submitted_parts', 1);

    expect(Part::pluck('filename')->all())->toBe(['parts/textures/texture.png']);
});

it('refuses the submission when every file has errors', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [invalidPartUpload()]])
        ->call('create')
        ->assertHasErrors(['data.partfiles' => 'There are no files without errors'])
        ->assertNotDispatched('open-modal');

    expect(errorsByFilename($component->get('fileStates')))->toBe(['bad.dat' => true])
        ->and(Part::count())->toBe(0);
});

it('does not let the client change the file validation state', function () {
    Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->set('fileStates', []);
})->throws(CannotUpdateLockedPropertyException::class);

it('clears and restores the replace error as the replace toggle changes', function () {
    Part::factory()->unofficial()->create(['filename' => 'parts/textures/texture.png']);

    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload()]]);

    $replaceErrorCount = fn (): int => collect($component->get('fileStates'))
        ->flatMap(fn (array $state) => $state['messages'])
        ->filter(fn ($message): bool => $message->check === PartError::ReplaceNotSelected)
        ->count();

    expect($replaceErrorCount())->toBe(1);

    $component->fillForm(['replace' => true]);
    expect($replaceErrorCount())->toBe(0)
        ->and(errorsByFilename($component->get('fileStates')))->toBe(['texture.png' => false]);

    $component->fillForm(['replace' => false]);
    expect($replaceErrorCount())->toBe(1);
});
