<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Part\Validator as BaseValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invalidBodyLine(): string
{
    return '1 1000000 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat';
}

it('check and saves error', function () {
    // Create a part with an error
    $part = Part::factory()
        ->withBody(invalidBodyLine())
        ->createQuietly([
            'type' => PartType::Part,
            'category' => PartCategory::Brick,
        ]);
    app(BaseValidator::class)->checkPart($part);
    expect($part->check_messages->count())->toBeGreaterThan(0);
});

it('updates unofficial can release', function () {
    // Create a part with an error
    $part = Part::factory()
        ->unofficial()
        ->withBody(invalidBodyLine())
        ->createQuietly([
            'type' => PartType::Part,
            'category' => PartCategory::Brick,
        ]);
    app(BaseValidator::class)->checkPart($part);
    expect($part->can_release)->toBeFalse();
});

it('does not update official can release', function () {
    // Create a part with an error
    $part = Part::factory()
        ->official()
        ->withBody(invalidBodyLine())
        ->createQuietly([
            'type' => PartType::Part,
            'category' => PartCategory::Brick,
        ]);
    app(BaseValidator::class)->checkPart($part);
    expect($part->can_release)->toBeTrue();
});

