<?php

use App\Enums\License;
use App\Jobs\MassHeaderGenerate;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Services\User\SyncUserParts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

/**
 * @return array<int, int> the part ids the queued header regeneration covers
 */
function regeneratedPartIds(): array
{
    return collect(Queue::pushed(MassHeaderGenerate::class)->sole()->partIds)->sort()->values()->all();
}

it('moves the parts of a user whose license changes to the new license', function () {
    $user = User::factory()->create(['license' => License::CC_BY_2]);
    $part = Part::factory()->create(['user_id' => $user->id, 'license' => License::CC_BY_2]);
    $user->license = License::CC_BY_4;
    $user->save();

    app(SyncUserParts::class)->handle($user, $user->getChanges());

    expect($part->fresh()->license)->toBe(License::CC_BY_4)
        ->and(regeneratedPartIds())->toBe([$part->id]);
});

it('regenerates the headers of parts whose history names a user whose username changes', function () {
    $user = User::factory()->create();
    $ownPart = Part::factory()->create(['user_id' => $user->id]);
    $historyPart = Part::factory()->create();
    PartHistory::factory()->create(['user_id' => $user->id, 'part_id' => $historyPart->id]);
    $user->name = 'RenamedUser';
    $user->save();

    app(SyncUserParts::class)->handle($user, $user->getChanges());

    expect(regeneratedPartIds())->toBe([$ownPart->id, $historyPart->id]);
});

it('ignores changes that do not appear in part headers', function () {
    $user = User::factory()->create();
    Part::factory()->create(['user_id' => $user->id]);
    $user->timezone = 'Europe/Paris';
    $user->save();

    app(SyncUserParts::class)->handle($user, $user->getChanges());

    Queue::assertNotPushed(MassHeaderGenerate::class);
});
