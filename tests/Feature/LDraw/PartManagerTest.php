<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('add or change part', function () {
    $u = User::factory()->create([
        'name' => 'James Jessiman',
        'realname' => 'James Jessiman',
        'is_legacy' => true,
    ]);
    User::factory()->create([
        'name' => 'PTadmin',
        'realname' => 'PTadmin',
        'is_ptadmin' => true,
    ]);
    User::factory()->create([
        'name' => 'Steffen',
        'realname' => 'Steffen',
    ]);
    User::factory()->create([
        'name' => 'westrate',
        'realname' => 'Andrew Westrate',
    ]);
    User::factory()->create([
        'name' => 'unknown',
        'realname' => 'unknown',
        'is_synthetic' => true,
    ]);
    $manager = app(\App\LDraw\PartManager::class);
    $part = $manager->submit([['contents' => file_get_contents(__DIR__ . '/testfiles/parts/3001.dat'), 'filename' => '3001.dat', 'type' => 'text']], $u)->first();
    expect($part->user->id)->toBe($u->id);
    expect($part->release)->toBe(null);
    expect($part->history->count())->toBe(6);
    expect($part->part_license_id)->toBe($part->user->part_license_id);
    expect((array)$part->missing_parts)->toBe(['s\\3001s01.dat']);
    $spart = $manager->submit([['contents' => file_get_contents(__DIR__ . '/testfiles/parts/s/3001s01.dat'), 'filename' => '3001s01.dat', 'type' => 'text']], $u)->first();
    expect($spart->parents->find($part->id))->not->toBeNull();
    $part->refresh();
    expect((array)$part->missing_parts)->toBe([]);
    expect($part->subparts->find($spart->id))->not->toBeNull();
    expect($manager->movePart($spart, '3001as01.dat', $spart->type))->toBe(true);
    expect($spart->filename)->toBe('parts/s/3001as01.dat');
    $part->body->refresh();
    expect(strpos($part->body->body, $spart->name()))->not->toBe(false);
});
