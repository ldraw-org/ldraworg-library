<?php

use App\Enums\Permission;
use App\Livewire\Part\Submit;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Check\CheckMessage;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\PartError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

function brickPartUpload(string $filename = '3001.dat'): UploadedFile
{
    User::factory()->create(['name' => 'TestUser', 'realname' => 'Test User']);

    return UploadedFile::fake()->createWithContent($filename, implode("\r\n", [
        '0 Brick  2 x  4',
        '0 Name: 3001.dat',
        '0 Author: Test User [TestUser]',
        '0 !LDRAW_ORG Unofficial_Part',
        '0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt',
    ]));
}

/**
 * @param  array<string, array{messages: iterable<CheckMessage>}>  $fileStates
 * @return Collection<int, CheckItem>
 */
function checksIn(array $fileStates): Collection
{
    return collect($fileStates)
        ->flatMap(fn (array $state) => $state['messages'])
        ->map(fn (CheckMessage $message): CheckItem => $message->check);
}

it('rejects a part file whose Name: line does not match its filename', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [brickPartUpload('3001a.dat')]]);

    expect(checksIn($component->get('fileStates')))->toContain(PartError::NameAndFilenameNotEqual);
});

it('rejects a part whose name is already used in the other parts folder', function () {
    Part::factory()->create(['filename' => 'p/3001.dat']);

    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [brickPartUpload()]]);

    expect(checksIn($component->get('fileStates')))->toContain(PartError::DuplicateFile);
});

it('does not ask to replace a texture whose name only ends with the uploaded filename', function () {
    Part::factory()->unofficial()->create(['filename' => 'parts/textures/xtexture.png']);

    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload()]]);

    expect(errorsByFilename($component->get('fileStates')))->toBe(['texture.png' => false]);
});

it('rejects every file that would be saved under the same name until only one is left', function () {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload('texture.png'), texmapUpload('Texture.png')]]);

    $states = collect($component->get('fileStates'));
    expect($states->pluck('hasErrors')->all())->toBe([true, true])
        ->and(checksIn($component->get('fileStates'))->unique()->values()->all())->toBe([PartError::DuplicateInSubmission]);

    $component->call('callSchemaComponentMethod', 'form.partfiles', 'removeUploadedFile', ['fileKey' => $states->keys()->first()]);

    expect(collect($component->get('fileStates'))->pluck('hasErrors')->all())->toBe([false]);
});

it('submits nothing when every file shares its name with another', function () {
    Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload('texture.png'), texmapUpload('texture.png')]])
        ->call('create')
        ->assertHasErrors(['data.partfiles' => 'There are no files without errors']);

    expect(Part::count())->toBe(0);
});

it('checks texmap filenames once lowercased', function (string $filename, bool $hasErrors) {
    $component = Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload($filename)]]);

    expect(errorsByFilename($component->get('fileStates')))->toBe([$filename => $hasErrors]);
})->with([
    'valid' => ['texture-1_a.png', false],
    'upper case' => ['Texture.PNG', false],
    'space' => ['my texture.png', true],
    'other character' => ['texture#1.png', true],
]);

it('refuses to submit once the user may no longer submit', function () {
    $user = submitter();
    $component = Livewire::actingAs($user)
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload()]]);

    $user->ca_confirm = false;
    $user->save();

    $component->call('create')->assertForbidden();
    expect(Part::count())->toBe(0);
});

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
