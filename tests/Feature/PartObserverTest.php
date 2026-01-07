<?php

use App\Models\Part\Part;
use App\Observers\PartObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Attach the observer manually for testing
    Part::observe(PartObserver::class);

    // Disable events not relevant to this test
    Bus::fake();
    Log::spy();
});

test('saving triggers generateHeader when a watched field is dirty', function () {
    // Arrange: Make a real part
    $part = Part::factory()->create([
        'filename' => '3001.dat',
        'description' => 'Brick',
    ]);

    // Make the model dirty
    $part->filename = '3001a.dat';

    // Mock ONLY generateHeader() on this instance
    $mock = Mockery::mock($part)->shouldAllowMockingProtectedMethods()->makePartial();
    $mock->shouldReceive('generateHeader')->once()->with(false);

    // Act: call the observer directly
    $observer = new \App\Observers\PartObserver();
    $observer->saving($mock);
});

test('saving does not trigger generateHeader when no watched fields are dirty', function () {
    // Arrange: Make a real part
    $part = Part::factory()->create([
        'filename' => '3001.dat',
        'description' => 'Brick',
    ]);

    // Modify a field NOT involved in the dirty check
    $part->updated_at = now();

    // Mock only generateHeader() so we can assert it's NOT called
    $mock = Mockery::mock($part)->shouldAllowMockingProtectedMethods()->makePartial();
    $mock->shouldReceive('generateHeader')->never();

    // Act: invoke the observer directly
    $observer = new \App\Observers\PartObserver();
    $observer->saving($mock);
});
