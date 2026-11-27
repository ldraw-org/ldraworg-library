<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Part\GenerateHeader;
use App\Services\Part\PartMover;
use App\Services\Part\SyncSubparts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('updates the description and header of the moved part that points at a renamed part', function () {
    Queue::fake();
    $target = Part::factory()->unofficial()->create(['filename' => 'parts/1234.dat', 'type' => PartType::Part, 'category' => PartCategory::Brick]);
    $movedTo = Part::factory()->unofficial()->withBody('1 16 0 0 0 1 0 0 0 1 0 0 0 1 1234.dat')->create([
        'filename' => 'parts/9999.dat',
        'type' => PartType::Part,
        'category' => PartCategory::Moved,
        'description' => '~Moved to 1234',
    ]);
    app(SyncSubparts::class)->loadSubparts($movedTo, true);
    app(GenerateHeader::class)->updatePartHeader($movedTo);

    app(PartMover::class)->moveUnofficialPart($target, newName: '5678.dat');

    expect($movedTo->fresh())
        ->description->toBe('~Moved to 5678')
        ->header->toContain('0 ~Moved to 5678')
        ->body->body->toContain('5678.dat');
});
