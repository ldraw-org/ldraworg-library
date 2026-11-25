<?php

use App\Jobs\CheckPart;
use App\Jobs\GeneratePartImage;
use App\Jobs\UpdateRebrickable;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use App\Services\Part\Release\PostReleaseCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('refreshes each released part and every part using it exactly once', function () {
    Storage::fake('local');
    foreach (['CAreadme.txt', 'CAlicense.txt', 'CAlicense4.txt'] as $file) {
        Storage::put("library/official/{$file}", 'text');
    }
    $release = PartRelease::factory()->create(['enabled' => true]);
    $previous = PartRelease::factory()->create(['enabled' => true]);
    $primitive = Part::factory()->official($release)->primitive()->create();
    // A diamond: the model reaches the primitive through two parts, so there are two paths to it
    $left = Part::factory()->official($previous)->inPartsFolder()->create();
    $right = Part::factory()->official($previous)->inPartsFolder()->create();
    $model = Part::factory()->official($previous)->inPartsFolder()->create();
    $unrelated = Part::factory()->official($previous)->inPartsFolder()->create();
    DB::table('related_parts')->insert([
        ['parent_id' => $left->id, 'subpart_id' => $primitive->id],
        ['parent_id' => $right->id, 'subpart_id' => $primitive->id],
        ['parent_id' => $model->id, 'subpart_id' => $left->id],
        ['parent_id' => $model->id, 'subpart_id' => $right->id],
    ]);
    Queue::fake([CheckPart::class, GeneratePartImage::class, UpdateRebrickable::class]);

    app(PostReleaseCleanup::class)->handle($release);

    $checked = Queue::pushed(CheckPart::class)
        ->map(fn (CheckPart $job): int => (fn () => $this->parts->first()->id)->call($job))
        ->sort()
        ->values()
        ->all();
    expect($checked)->toBe(collect([$primitive->id, $left->id, $right->id, $model->id])->sort()->values()->all())
        ->and($checked)->not->toContain($unrelated->id);
    Queue::assertPushedTimes(GeneratePartImage::class, 4);
});
