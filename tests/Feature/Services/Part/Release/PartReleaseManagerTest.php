<?php

use App\Enums\EventType;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\ReleaseStage;
use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Jobs\GenerateReleaseImage;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Models\Part\PartReleaseRun;
use App\Models\RebrickablePart;
use App\Models\User;
use App\Services\Part\Release\PartReleaseManager;
use App\Settings\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('releases marked parts, fixes and minor edits and publishes the update', function () {
    $this->travelTo('2026-06-15 12:00:00');
    Storage::fake('local');
    Storage::fake(config('media-library.disk_name'));
    foreach (['CAreadme.txt', 'CAlicense.txt', 'CAlicense4.txt'] as $file) {
        Storage::put("library/official/{$file}", 'text');
    }
    Storage::put('library/updates/complete.zip', 'old zip');
    Storage::put('library/updates/LDrawParts.exe', 'old exe');
    $previous = PartRelease::factory()->create(['name' => '2025-07', 'short' => '2507', 'created_at' => '2025-12-01', 'enabled' => true]);
    $actor = User::factory()->create();

    $rebrickablePart = RebrickablePart::create(['number' => '3001a', 'name' => 'Brick 2 x 4', 'ldraw' => ['3001']]);
    $newPart = Part::factory()->unofficial()->create([
        'filename' => 'parts/3001.dat',
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        'rebrickable_part_id' => $rebrickablePart->id,
        'description' => 'Brick 2 x 4',
        'part_status' => PartStatus::Certified,
        'marked_for_release' => true,
    ]);
    $official = Part::factory()->official($previous)->withFix()->create([
        'filename' => 'parts/3002.dat',
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
        'description' => 'Brick 2 x 3 Old',
        'has_minor_edit' => true,
    ]);
    $fix = $official->unofficial_part;
    $fix->update(['description' => 'Brick 2 x 3', 'type' => PartType::Part, 'category' => PartCategory::Brick, 'part_status' => PartStatus::Certified, 'marked_for_release' => true]);
    $fixImage = $fix->addMedia(UploadedFile::fake()->image('3002.png'))->toMediaCollection('image');
    $minorEdit = Part::factory()->official($previous)->create(['filename' => 'parts/3003.dat', 'has_minor_edit' => true]);
    $nextCycle = Part::factory()->unofficial()->create([
        'filename' => 'parts/3004.dat',
        'part_status' => PartStatus::Certified,
        'can_release' => true,
    ]);
    $run = PartReleaseRun::factory()->create(['user_id' => $actor->id, 'stage' => ReleaseStage::BackingUp]);
    Queue::fake([GenerateReleaseImage::class, GeneratePartImage::class, CheckPart::class, UpdateRebrickable::class]);

    $release = app(PartReleaseManager::class)->createRelease($actor, run: $run);

    expect($release->fresh())
        ->name->toBe('2026-01')
        ->short->toBe('2601')
        ->enabled->toBeTrue()
        ->total->toBe(2)
        ->new->toBe(1);
    expect($run->fresh())
        ->stage->toBe(ReleaseStage::Complete)
        ->part_release_id->toBe($release->id)
        ->isRunning()->toBeFalse();
    expect($release->getMedia('view')->map->getCustomProperty('filename')->all())->toBe(['parts/3001.dat']);

    expect($newPart->fresh())
        ->part_release_id->toBe($release->id)
        ->marked_for_release->toBeFalse()
        ->header->toContain('0 !KEYWORDS Rebrickable 3001a');
    expect($newPart->history()->pluck('comment')->last())->toBe('Official Update 2026-01');

    $this->assertModelMissing($fix);
    $this->assertModelMissing($fixImage);
    Storage::disk(config('media-library.disk_name'))->assertMissing($fixImage->getPathRelativeToRoot());
    expect($official->fresh())
        ->part_release_id->toBe($release->id)
        ->description->toBe('Brick 2 x 3');
    expect($official->history()->pluck('comment')->all())->not->toContain('Minor header edits');
    expect($official->events()->where('event_type', EventType::Release)->where('part_release_id', $release->id)->exists())->toBeTrue();

    expect($minorEdit->fresh())
        ->part_release_id->toBe($release->id)
        ->has_minor_edit->toBeFalse()
        ->header->toContain('UPDATE 2026-01');
    expect($minorEdit->history()->pluck('comment')->all())->toBe(['Minor header edits', 'Official Update 2026-01']);

    expect($nextCycle->fresh()->marked_for_release)->toBeTrue();
    expect(app(LibrarySettings::class)->tracker_locked)->toBeFalse();

    Storage::assertExists([
        'library/updates/complete.zip',
        'library/updates/complete-2507.zip',
        'library/updates/LDraw2507.exe',
        'library/updates/lcad2601.zip',
        'library/unofficial/ldrawunf.zip',
    ]);
    expect(Storage::get('library/official/models/Note2601CA.txt'))->toBe(
        "ldraw.org Parts Update 2026-01\n" .
        str_repeat('-', 76) . "\n\n" .
        "Redistributable Parts Library - Core Library\n" .
        str_repeat('-', 76) . "\n\n" .
        "Notes created Mon, 15 Jun 2026 12:00:00 +0000 by the Parts Tracker\n\n" .
        "Release statistics:\n" .
        "   Total files: 2\n" .
        "   New files: 1\n" .
        "   New Parts: 1\n" .
        "\nMoved Parts\n" .
        "\nRenamed Parts\n" .
        "   3002.dat                   Brick 2 x 3 Old\n" .
        "   changed to    Brick 2 x 3\n" .
        "\nMinor Edits\n" .
        "   1 Parts with minor administrative edits and/or license changes\n"
    );
    Queue::assertPushed(GenerateReleaseImage::class);
});
