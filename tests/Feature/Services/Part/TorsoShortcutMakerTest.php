<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;
use App\Models\User;
use App\Services\Part\TorsoShortcutMaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('parses the template into placed subfiles with the official patterns as variations', function () {
    ['template' => $template, 'armPattern' => $armPattern] = torsoShortcutParts();
    Part::factory()->official()->withFix()->createQuietly([
        'filename' => 'parts/3818p02.dat',
        'type' => PartType::Part,
        'category' => PartCategory::MinifigArm,
        'base_part_id' => $armPattern->base_part_id,
        'is_pattern' => true,
    ]);
    Part::factory()->official()->createQuietly([
        'filename' => 'parts/3818p03.dat',
        'type' => PartType::Part,
        'category' => PartCategory::Obsolete,
        'base_part_id' => $armPattern->base_part_id,
        'is_pattern' => true,
    ]);

    $result = app(TorsoShortcutMaker::class)->template($template);

    expect($result)
        ->name->toBe('76382.dat')
        ->description->toBe('Minifig Torso Assembly')
        ->and($result['parts'])->toHaveCount(2)
        ->and($result['parts'][0])->toMatchArray(['name' => '973.dat', 'variations' => []])
        ->and($result['parts'][1])->toMatchArray([
            'name' => '3818.dat',
            'description' => 'Minifig Arm Right',
            'variations' => ['3818p01.dat' => '3818p01.dat - Minifig Arm Right with Stripe'],
        ])
        ->and(explode(' ', $result['parts'][1]['position']))->toHaveCount(12);
});

it('keeps a template line whose subfile is not in the library', function () {
    ['template' => $template] = torsoShortcutParts();
    $template->body->forceFill(['body' => '1 16 0 0 0 1 0 0 0 1 0 0 0 1 99999.dat'])->save();

    $result = app(TorsoShortcutMaker::class)->template($template);

    expect($result['parts'][0])->toMatchArray(['name' => '99999.dat', 'description' => '', 'variations' => []]);
});

it('derives the default name, description and keywords from the torso', function () {
    ['torso' => $torso] = torsoShortcutParts();
    $torso->keywords()->attach(
        collect(['Set 6399', 'BrickLink 973p01', 'Airport'])
            ->map(fn (string $keyword): int => PartKeyword::create(['keyword' => $keyword])->id)
    );
    $maker = app(TorsoShortcutMaker::class);

    expect($maker->defaultName('76382.dat', $torso))->toBe('76382p01.dat')
        ->and($maker->defaultDescription('Minifig Torso Assembly', $torso))->toBe('Minifig Torso Assembly with Logo Pattern')
        ->and($maker->defaultKeywords($torso->fresh()))->toBe('Airport, Set 6399');
});

it('builds the shortcut file with the torso, chosen variations, colours and external site keywords', function () {
    ['template' => $template, 'torso' => $torso] = torsoShortcutParts();
    $user = User::factory()->create();
    $maker = app(TorsoShortcutMaker::class);

    $text = $maker->text($maker->template($template), [
        'description' => 'Minifig Torso Assembly with Logo Pattern',
        'name' => '76382p01',
        'keywords' => 'Set 6399',
        'bricklink' => '973p01c01',
        'rebrickable' => '',
        'part_0_color' => 4,
        'part_1_part' => '3818p01.dat',
    ], $torso, $user);

    expect(explode("\n", $text))
        ->toContain(
            '0 Minifig Torso Assembly with Logo Pattern',
            '0 Name: 76382p01.dat',
            $user->toString(),
            '0 BFC CERTIFY CCW',
            PartCategory::MinifigUpper->ldrawString(),
            '0 !KEYWORDS BrickLink 973p01c01, Set 6399',
        )
        ->and($text)
        ->toMatch('/^1 4 .* 973p01\.dat$/m')
        ->toMatch('/^1 16 .* 3818p01\.dat$/m');
});

it('adds the .dat extension to a name only when it is missing', function (string $name) {
    expect(app(TorsoShortcutMaker::class)->normalizeName($name))->toBe('76382p01.dat');
})->with(['76382p01', '76382p01.dat']);
