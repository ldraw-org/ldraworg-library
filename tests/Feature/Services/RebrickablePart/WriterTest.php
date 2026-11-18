<?php

use App\Models\RebrickablePart;
use App\Services\RebrickablePart\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the stored element when none is provided', function () {
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick', 'element' => '300121']);

    $part = app(Writer::class)->updateOrCreateFromArray(['part_num' => '3001', 'name' => 'Brick 2 x 4', 'part_cat_id' => 11, 'element' => null]);

    expect($part->fresh()->element)->toBe('300121');
    expect($part->fresh()->name)->toBe('Brick 2 x 4');
});

it('updates the stored element when one is provided', function () {
    RebrickablePart::create(['number' => '3001', 'name' => 'Brick', 'element' => '300121']);

    $part = app(Writer::class)->updateOrCreateFromArray(['part_num' => '3001', 'name' => 'Brick', 'part_cat_id' => 11, 'element' => '300126']);

    expect($part->fresh()->element)->toBe('300126');
});

it('stores print_of and keeps it when none is provided', function () {
    $writer = app(Writer::class);

    $part = $writer->updateOrCreateFromArray(['part_num' => '3001pr0001', 'name' => 'Printed Brick', 'part_cat_id' => 11, 'print_of' => '3001']);
    expect($part->fresh()->print_of)->toBe('3001');

    $writer->updateOrCreateFromArray(['part_num' => '3001pr0001', 'name' => 'Printed Brick', 'part_cat_id' => 11]);
    expect($part->fresh()->print_of)->toBe('3001');
});
