<?php

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Part\TexmapOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('losslessly optimizes texmap bodies and flags changed official parts as minor edits', function () {
    Storage::fake('local');
    foreach (['CAreadme.txt', 'CAlicense.txt', 'CAlicense4.txt'] as $file) {
        Storage::put("library/official/{$file}", 'text');
    }
    $uncompressed = uncompressedTexmapPng();
    $alreadyOptimized = app(TexmapOptimizer::class)->optimize($uncompressed);
    $texmap = fn (string $name, string $png, bool $official): Part => tap(
        Part::factory()->{$official ? 'official' : 'unofficial'}()->create(['type' => PartType::PartTexmap, 'filename' => "parts/textures/{$name}.png"]),
        fn (Part $part) => $part->setBody(base64_encode($png))
    );
    $official = $texmap('official', $uncompressed, true);
    $unofficial = $texmap('unofficial', $uncompressed, false);
    $unchanged = $texmap('unchanged', $alreadyOptimized, true);

    $this->artisan('lib:mass-update')
        ->expectsOutputToContain('Optimized 2 texmaps')
        ->assertSuccessful();

    foreach ([$official, $unofficial] as $part) {
        $body = $part->fresh()->get();
        expect(strlen($body))->toBeLessThan(strlen($uncompressed))
            ->and(texmapPixels($body))->toBe(texmapPixels($uncompressed));
    }
    expect($official->fresh()->has_minor_edit)->toBeTrue()
        ->and($unofficial->fresh()->has_minor_edit)->toBeFalse()
        ->and($unchanged->fresh()->has_minor_edit)->toBeFalse()
        ->and($unchanged->fresh()->get())->toBe($alreadyOptimized);
    Storage::assertExists('library/unofficial/ldrawunf.zip');
});
