<?php

// tests/Unit/Services/Part/WriterTest.php

use App\Models\Part\Part;
use App\Services\BackupFile;
use App\Services\Part\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Minimal valid $values array.
 * Adjust keys to match your actual Part $fillable / factory columns.
 */
function writerValues(array $overrides = []): array
{
    return array_merge([
        'filename'    => 'parts/test.dat',
        'description' => 'Test Part',
        // add other required fillable columns here
    ], $overrides);
}

/**
 * Bind a BackupFile mock into the service container and return it
 * so individual tests can add expectations.
 */
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
        $existing = Part::factory()->unofficial()->create(['filename' => 'parts/test.dat']);
        $expectedSlug = str_replace('/', '-', $existing->filename); // 'parts-test.dat'
        $expectedContent = $existing->get();

        $backup = mockBackupFile();
        $backup->shouldReceive('handle')
            ->once()
            ->with($expectedSlug, $expectedContent);

        // Act
        app(Writer::class)->createOrUpdate(writerValues());

        // Assert — Mockery expectation above covers it
    });

    it('deletes all votes on the existing unofficial part', function () {
        $existing = Part::factory()->unofficial()->hasVotes(3)->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues());

        expect($existing->votes()->count())->toBe(0);
    });

    it('fills the existing record with the new values instead of creating a new row', function () {
        $existing = Part::factory()->unofficial()->create([
            'filename'    => 'parts/test.dat',
            'description' => 'Old Description',
        ]);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues(['description' => 'New Description']));

        expect(Part::unofficial()->count())->toBe(1)        // no extra row
        ->and($existing->fresh()->description)->toBe('New Description');
    });

    it('does not touch the official part', function () {
        $official = Part::factory()->official()->create(['filename' => 'parts/test.dat']);
        Part::factory()->unofficial()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        app(Writer::class)->createOrUpdate(writerValues());

        // Official record unchanged — unofficial_part association untouched
        expect($official->fresh()->unofficial_part_id)->toBeNull();
    });

    it('returns the refreshed unofficial part', function () {
        $existing = Part::factory()->unofficial()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle');

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Updated']));

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
        Part::factory()->official()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never(); // no backup for a new part

        expect(Part::unofficial()->count())->toBe(0);

        app(Writer::class)->createOrUpdate(writerValues());

        expect(Part::unofficial()->count())->toBe(1);
    });

    it('associates the new unofficial part with the official part', function () {
        $official = Part::factory()->official()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues());

        $upart = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($official->fresh()->unofficial_part_id)->toBe($upart->id);
    });

    it('returns the newly created unofficial part', function () {
        Part::factory()->official()->create(['filename' => 'parts/test.dat']);
        mockBackupFile()->shouldReceive('handle')->never();

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Brand New']));

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

        app(Writer::class)->createOrUpdate(writerValues());

        expect(Part::unofficial()->count())->toBe(1);
    });

    it('does not call BackupFile', function () {
        $backup = mockBackupFile();
        $backup->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues());

        // Mockery will assert 'never' on teardown
    });

    it('returns the new part', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        $result = app(Writer::class)->createOrUpdate(writerValues(['description' => 'Fresh']));

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

        app(Writer::class)->createOrUpdate(
            values: writerValues(),
            bodyText: '',
            keywords: ['ALIAS', 'HELP'],
        );

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->keywords->pluck('keyword')->all())
            ->toContain('ALIAS')
            ->toContain('HELP');
    });

    it('persists the provided history lines', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(
            values: writerValues(),
            bodyText: '',
            keywords: [],
            history: ['0 !HISTORY 2024-01-01 [Author] Initial'],
        );

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->history->first()->body)
            ->toBe('0 !HISTORY 2024-01-01 [Author] Initial');
    });

    it('stores the body text quietly (without triggering observers)', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(
            values: writerValues(),
            bodyText: '0 Test part body',
        );

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        // Adjust the accessor name to match your actual body storage column/method
        expect($part->body)->toBe('0 Test part body');
    });

    it('defaults keywords and history to empty when not supplied', function () {
        mockBackupFile()->shouldReceive('handle')->never();

        app(Writer::class)->createOrUpdate(writerValues(), '');

        $part = Part::unofficial()->firstWhere('filename', 'parts/test.dat');
        expect($part->keywords)->toBeEmpty()
            ->and($part->history)->toBeEmpty();
    });
});
