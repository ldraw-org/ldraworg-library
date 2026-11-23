<?php

use App\Enums\License;
use App\Enums\Permission;
use App\Jobs\GenerateOmrModelImage;
use App\Livewire\Omr\Set\Show;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\Part\Part;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
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
    Queue::fake([GenerateOmrModelImage::class]);
    Storage::fake(config('media-library.disk_name'));
    Role::findOrCreate('OMR Author');
    Theme::create(['id' => 1, 'name' => 'Town']);
    $this->set = Set::create(['number' => '6399-1', 'name' => 'Airport Shuttle', 'year' => 1990, 'rb_url' => '', 'theme_id' => 1]);
    $this->author = User::factory()->create(['name' => 'ModelMaker', 'realname' => 'Model Maker']);
    Part::factory()->official()->create(['filename' => 'parts/3001.dat']);

    $this->model = OmrModel::create([
        'user_id' => $this->author->id,
        'set_id' => $this->set->id,
        'approved' => true,
        'missing_parts' => false,
        'missing_patterns' => false,
        'missing_stickers' => false,
        'alt_model' => false,
        'notes' => ['notes' => 'Original notes'],
        'license' => License::CC_BY_2,
    ]);
    $this->model->addMediaFromString('0 Original')->usingFilename('6399-1.mpd')->toMediaCollection('file');

    $editor = User::factory()->create();
    $editor->givePermissionTo(Permission::OmrModelEdit->value);
    $this->actingAs($editor);
});

function editModel(OmrModel $model): TestAction
{
    return TestAction::make('editModel')->arguments(['model' => $model->id]);
}

it('hides the edit button from users who cannot edit OMR models', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Show::class, ['set' => $this->set])
        ->assertActionHidden(editModel($this->model));
});

it('fills the form with the model details', function () {
    Livewire::test(Show::class, ['set' => $this->set])
        ->mountAction(editModel($this->model))
        ->assertActionDataSet([
            'user_id' => $this->author->id,
            'alt_model' => false,
            'notes' => 'Original notes',
        ]);
});

it('updates the details and renames the file when the model becomes an alternate', function () {
    $newAuthor = User::factory()->create(['license' => License::CC0]);
    OmrModel::create(['user_id' => $this->author->id, 'set_id' => $this->set->id, 'approved' => true, 'missing_parts' => false, 'missing_patterns' => false, 'missing_stickers' => false, 'alt_model' => true, 'alt_model_name' => 'B Model', 'notes' => ['notes' => ''], 'license' => License::CC_BY_4]);

    Livewire::test(Show::class, ['set' => $this->set])
        ->callAction(editModel($this->model), data: [
            'user_id' => $newAuthor->id,
            'alt_model' => true,
            'alt_model_name' => 'C Model',
            'missing_stickers' => true,
            'notes' => 'New notes',
        ])
        ->assertNotified('Model updated');

    $this->model->refresh();
    expect($this->model->only(['user_id', 'license', 'alt_model', 'alt_model_name', 'missing_stickers', 'notes']))->toBe([
        'user_id' => $newAuthor->id,
        'license' => License::CC0,
        'alt_model' => true,
        'alt_model_name' => 'C Model',
        'missing_stickers' => true,
        'notes' => ['notes' => 'New notes'],
    ])
        ->and($this->model->getFirstMedia('file')->file_name)->toBe('6399-1_C-Model.mpd')
        ->and($newAuthor->fresh()->hasRole('OMR Author'))->toBeTrue();
    Queue::assertNotPushed(GenerateOmrModelImage::class);
});

it('keeps the license when the author does not change', function () {
    Livewire::test(Show::class, ['set' => $this->set])
        ->callAction(editModel($this->model), data: ['missing_patterns' => true])
        ->assertNotified('Model updated');

    expect($this->model->refresh()->license)->toBe(License::CC_BY_2);
});

it('rejects an alternate name or main model already used by another model in the set', function () {
    OmrModel::create(['user_id' => $this->author->id, 'set_id' => $this->set->id, 'approved' => true, 'missing_parts' => false, 'missing_patterns' => false, 'missing_stickers' => false, 'alt_model' => true, 'alt_model_name' => 'B Model', 'notes' => ['notes' => ''], 'license' => License::CC_BY_4]);
    $altModel = OmrModel::firstWhere('alt_model_name', 'B Model');

    Livewire::test(Show::class, ['set' => $this->set])
        ->callAction(editModel($this->model), data: ['alt_model' => true, 'alt_model_name' => 'B Model'])
        ->assertHasFormErrors(['alt_model_name']);

    Livewire::test(Show::class, ['set' => $this->set])
        ->callAction(editModel($altModel), data: ['alt_model' => false])
        ->assertHasFormErrors(['alt_model']);
});

it('replaces the model file and checks it against the model set', function () {
    Livewire::test(Show::class, ['set' => $this->set])
        ->mountAction(editModel($this->model))
        ->setActionData(['modelfile' => omrModelFile('6400-1.mpd')])
        ->assertSet('checks.warnings', [
            'The file is for set 6400-1, not 6399-1',
            'Parts not in the official library: 99999.dat',
        ])
        ->assertActionDataSet(['missing_parts' => true])
        ->callMountedAction()
        ->assertNotified('Model updated');

    $file = $this->model->refresh()->getFirstMedia('file');
    expect($file->file_name)->toBe('6399-1.mpd')
        ->and(file_get_contents($file->getPath()))->toContain('3001.dat')
        ->and($this->model->missing_parts)->toBeTrue();
    Queue::assertPushed(GenerateOmrModelImage::class);
});

it('rejects a replacement file that is not an LDraw model', function () {
    Livewire::test(Show::class, ['set' => $this->set])
        ->mountAction(editModel($this->model))
        ->setActionData(['modelfile' => UploadedFile::fake()->image('6399-1.mpd')])
        ->callMountedAction()
        ->assertHasFormErrors(['modelfile' => 'The model file has errors']);

    expect(file_get_contents($this->model->refresh()->getFirstMedia('file')->getPath()))->toBe('0 Original');
});
