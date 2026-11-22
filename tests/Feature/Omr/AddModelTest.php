<?php

use App\Enums\Permission;
use App\Jobs\GenerateOmrModelImage;
use App\Livewire\Omr\OmrModel\Add;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    Role::findOrCreate('OMR Author');
    Theme::create(['id' => 1, 'name' => 'Town']);
    $this->set = Set::create(['number' => '6399-1', 'name' => 'Airport Shuttle', 'year' => 1990, 'rb_url' => '', 'theme_id' => 1]);
    $this->author = User::factory()->create(['name' => 'ModelMaker', 'realname' => 'Model Maker']);
    Part::factory()->official()->create(['filename' => 'parts/3001.dat']);

    $approver = User::factory()->create();
    $approver->givePermissionTo(Permission::OmrModelApprove->value);
    $this->actingAs($approver);
});

function omrModelFile(string $filename = '6399-1 - Airport Shuttle.mpd', string $author = 'Model Maker [ModelMaker]'): UploadedFile
{
    $text = implode("\r\n", [
        "0 FILE {$filename}",
        '0 Airport Shuttle',
        "0 Name: {$filename}",
        "0 Author: {$author}",
        '1 16 0 0 0 1 0 0 0 1 0 0 0 1 cabin.ldr',
        '0 FILE cabin.ldr',
        '1 4 0 0 0 1 0 0 0 1 0 0 0 1 3001.dat',
        '1 4 0 0 0 1 0 0 0 1 0 0 0 1 99999.dat',
    ]) . "\r\n";

    return UploadedFile::fake()->createWithContent($filename, $text);
}

it('fills the set, author and missing parts from the uploaded model', function () {
    Livewire::test(Add::class)
        ->fillForm(['modelfile' => omrModelFile()])
        ->assertSchemaStateSet([
            'set_id' => $this->set->id,
            'user_id' => $this->author->id,
            'alt_model' => false,
            'missing_parts' => true,
        ])
        ->assertSet('checks.errors', [])
        ->assertSet('checks.warnings', ['Parts not in the official library: 99999.dat']);
});

it('marks the upload as an alternate model when the set already has a main model', function () {
    OmrModel::create(['user_id' => $this->author->id, 'set_id' => $this->set->id, 'approved' => true, 'missing_parts' => false, 'missing_patterns' => false, 'missing_stickers' => false, 'alt_model' => false, 'notes' => ['notes' => ''], 'license' => $this->author->license]);

    Livewire::test(Add::class)
        ->fillForm(['modelfile' => omrModelFile()])
        ->assertSchemaStateSet(['alt_model' => true]);
});

it('warns when the set or author cannot be found', function () {
    Livewire::test(Add::class)
        ->fillForm(['modelfile' => omrModelFile('10000-1.mpd', 'Nobody [nobody]')])
        ->assertSchemaStateSet(['set_id' => null, 'user_id' => null])
        ->assertSet('checks.warnings', [
            'Set 10000-1 is not in the OMR yet, add it from the set field',
            'The author in the file header does not match a library user',
            'Parts not in the official library: 99999.dat',
        ]);
});

it('rejects a file that is not an LDraw model', function () {
    Livewire::test(Add::class)
        ->fillForm(['modelfile' => UploadedFile::fake()->image('6399-1.mpd')])
        ->assertSet('checks.errors', ['The file is not an LDraw text file'])
        ->call('create')
        ->assertHasFormErrors(['modelfile' => 'The model file has errors']);

    expect(OmrModel::count())->toBe(0);
});

it('adds the uploaded model to the set', function () {
    Queue::fake([GenerateOmrModelImage::class]);
    Storage::fake(config('media-library.disk_name'));

    Livewire::test(Add::class)
        ->fillForm(['modelfile' => omrModelFile(), 'notes' => 'Built from instructions'])
        ->call('create')
        ->assertHasNoFormErrors();

    $model = OmrModel::sole();
    expect($model->only(['user_id', 'set_id', 'alt_model', 'missing_parts', 'approved', 'notes']))->toBe([
        'user_id' => $this->author->id,
        'set_id' => $this->set->id,
        'alt_model' => false,
        'missing_parts' => true,
        'approved' => true,
        'notes' => ['notes' => 'Built from instructions'],
    ])
        ->and($model->getFirstMedia('file')->file_name)->toBe('6399-1.mpd')
        ->and($this->author->fresh()->hasRole('OMR Author'))->toBeTrue();
    Queue::assertPushed(GenerateOmrModelImage::class);
});

it('refuses users who cannot approve OMR models', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('omr.add'))
        ->assertForbidden();
});
