<?php

use App\Enums\EventType;
use App\Enums\PartType;
use App\Jobs\CheckPart;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\User;
use App\Services\Part\Remover;
use App\Services\Part\SyncSubparts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('deletes a part, keeping a backup, and marks it missing in the parts that used it', function () {
    Storage::fake();
    Queue::fake();
    $actor = User::factory()->create();
    $subpart = Part::factory()->unofficial()->create(['filename' => 'parts/s/1234s01.dat', 'type' => PartType::Subpart, 'category' => null]);
    $parent = Part::factory()->unofficial()->withBody('1 16 0 0 0 1 0 0 0 1 0 0 0 1 s\1234s01.dat')->create();
    app(SyncSubparts::class)->loadSubparts($parent, true);
    expect($parent->fresh()->subparts->modelKeys())->toBe([$subpart->id]);

    // A freshly loaded part, so nothing has loaded its parents before the delete cascades to them
    app(Remover::class)->delete(Part::find($subpart->id), $actor);

    expect(Part::find($subpart->id))->toBeNull()
        ->and($parent->fresh())
        ->subparts->toBeEmpty()
        ->missing_parts->toBe(['s/1234s01.dat'])
        ->and(PartEvent::sole())
        ->event_type->toBe(EventType::Delete)
        ->user_id->toBe($actor->id)
        ->deleted_filename->toBe('parts/s/1234s01.dat')
        ->and(Storage::allFiles('deleted/library/parts/s'))->toHaveCount(2);
    Queue::assertPushed(CheckPart::class);
});
