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

function invalidPartUpload(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('bad.dat', "0 Not an LDraw part\r\n1 16 garbage\r\n");
}

it('forbids users who have not confirmed the contributor agreement', function () {
    $user = User::factory()->create(['ca_confirm' => false]);
    $user->givePermissionTo(Permission::PartSubmitRegular->value);

    Livewire::actingAs($user)
        ->test(Submit::class)
        ->assertForbidden();
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

it('rejects a file with errors even after the client clears its error state', function () {
    Livewire::actingAs(submitter())
        ->test(Submit::class)
        ->fillForm(['partfiles' => [texmapUpload(), invalidPartUpload()]])
        ->call('checkFile', 'bad.dat')
        ->call('removeFile', 'bad.dat')
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

    expect($component->get('fileStates')['bad.dat']['hasErrors'])->toBeTrue()
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
        ->fillForm(['partfiles' => [texmapUpload()]])
        ->call('checkFile', 'texture.png');

    $replaceErrors = fn (): array => collect($component->get('fileStates')['texture.png']['messages'] ?? [])
        ->filter(fn ($message): bool => $message->check === PartError::ReplaceNotSelected)
        ->all();

    expect($replaceErrors())->toHaveCount(1);

    $component->fillForm(['replace' => true]);
    expect($component->get('fileStates'))->toBe([]);

    $component->fillForm(['replace' => false]);
    expect($replaceErrors())->toHaveCount(1);
});
