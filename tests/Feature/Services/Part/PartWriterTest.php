<?php

// tests/Unit/Services/Part/WriterTest.php

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\BackupFile;
use App\Services\Part\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function writerValues(array $overrides = []): array
{
    $user = User::factory()->create();
    return array_merge([
        'filename'    => 'parts/test.dat',
        'description' => 'Test Part',
        'category' => PartCategory::Brick,
        'type' => PartType::Part,
        'user_id' => $user->id,
        'license' => License::CC_BY_4,
        'bfc' => 'CCW',
        'missing_parts' => [],
    ], $overrides);
}

function bodyText(?string $override = null): string
{
    return $override ?? '4 16 0 0 0 0 1 0 1 1 0 1 0 0';
}

function mockBackupFile(): Mockery\MockInterface
{
    $mock = Mockery::mock(BackupFile::class);
    app()->instance(BackupFile::class, $mock);
    return $mock;
}

// ---------------------------------------------------------------------------
// Branch 1 — unofficial part already exists
// ---------------------------------------------------------------------------

describe('when an unofficial part already exists', function () {

    it('calls BackupFile::handle with a sanitised filename and the current file content', function () {
        // Arrange
        $existing = Part::factory()->unofficial()->createQuietly(['filename' => 'parts/test.dat']);
        $expectedSlug = str_replace('/', '-', $existing->filename); // 'parts-test.dat'
        $expectedContent = $existing->get();

        $backup = mockBackupFile();
        $backup->shouldReceive('handle')
            ->once()
            ->with($expectedSlug, $expectedContent);
        // Act
        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        // Assert — Mockery expectation above covers it
    });

    it('deletes all votes on the existing unofficial part', function () {
        $existing = Part::factory()->unofficial()->hasVotes(3)->createQuietly(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        expect($existing->votes()->count())->toBe(0);
    });

    it('fills the existing record with the new values instead of creating a new row', function () {
        $existing = Part::factory()->unofficial()->createQuietly([
            'filename'    => 'parts/test.dat',
            'description' => 'Old Description',
        ]);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues(['description' => 'New Description']), bodyText());

        expect(Part::unofficial()->count())->toBe(1)        // no extra row
        ->and($existing->fresh()->description)->toBe('New Description');
    });

    it('does not touch the official part', function () {
        $official = Part::factory()->official()->createQuietly(['filename' => 'parts/test.dat']);
        Part::factory()->unofficial()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        // Official record unchanged — unofficial_part association untouched
        expect($official->fresh()->unofficial_part_id)->toBeNull();
    });

    it('returns the refreshed unofficial part', function () {
        $existing = Part::factory()->unofficial()->createQuietly(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Updated']), bodyText());

        expect($result)->toBeInstanceOf(Part::class)
            ->and($result->id)->toBe($existing->id)
            ->and($result->description)->toBe('Updated');
    });
});

// ---------------------------------------------------------------------------
// Branch 2 — only an official part exists
// ---------------------------------------------------------------------------

describe('when only an official part exists', function () {

    it('creates a new unofficial part record', function () {
        Part::factory()->official()->createQuietly(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never(); // no backup for a new part

        expect(Part::unofficial()->count())->toBe(0);

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        expect(Part::unofficial()->count())->toBe(1);
    });

    it('associates the new unofficial part with the official part', function () {
        $official = Part::factory()->official()->createQuietly(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        $upart = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($official->fresh()->unofficial_part_id)->toBe($upart->id);
    });

    it('returns the newly created unofficial part', function () {
        Part::factory()->official()->createQuietly(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never();

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Brand New']), bodyText());

        expect($result)->toBeInstanceOf(Part::class)
            ->and($result->description)->toBe('Brand New');
    });
});

// ---------------------------------------------------------------------------
// Branch 3 — no existing part at all
// ---------------------------------------------------------------------------

describe('when neither an unofficial nor official part exists', function () {

    it('creates a brand new unofficial part', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        expect(Part::count())->toBe(0);

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        expect(Part::unofficial()->count())->toBe(1);
    });

    it('does not call BackupFile', function () {
        $backup = mockBackupFile();
        $backup->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        // Mockery will assert 'never' on teardown
    });

    it('returns the new part', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Fresh']), bodyText());

        expect($result)->toBeInstanceOf(Part::class)
            ->and($result->description)->toBe('Fresh');
    });
});

// ---------------------------------------------------------------------------
// Shared post-save behaviour (all branches)
// ---------------------------------------------------------------------------

describe('post-save steps applied in all branches', function () {

    // NOTE: setKeywords, setHistory, setBodyQuietly write to related tables /
    // columns, so we verify them via DB side-effects rather than call-spies.
    // If you later extract these behind an interface, swap to Mockery spies.

    it('persists the provided keywords', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues(), bodyText(), ['ALIAS', 'HELP']);

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->keywords->pluck('keyword')->all())
            ->toContain('ALIAS')
            ->toContain('HELP');
    });

    it('persists the provided history lines', function () {
        mockBackupFile()->shouldReceive('handle')->never();
        $user = User::factory()->create();
        $date = now()->format('Y-m-d');
        $comment = 'Test comment';
        $historyLine = "0 !HISTORY $date [$user->name] $comment";
        app(Writer::class)->createOrUpdate(
            values: writerValues(),
            bodyText: bodyText(),
            keywords: [],
            history: [
                [
                    'username' => $user->name,
                    'date' => $date,
                    'comment' => $comment,
                ]
            ],
        );

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->history->first()->toString())
            ->toBe($historyLine);
    });

    it('stores the body text quietly (without triggering observers)', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(
            values: writerValues(),
            bodyText: bodyText('1 16 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat'),
        );

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        // Adjust the accessor name to match your actual body storage column/method
        expect($part->body->body)->toBe('1 16 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat');
    });

    it('defaults keywords and history to empty when not supplied', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues(), bodyText());

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->keywords)->toBeEmpty()
            ->and($part->history)->toBeEmpty();
    });
});
