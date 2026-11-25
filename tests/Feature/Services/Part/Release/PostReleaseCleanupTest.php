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

beforeEach(function () {
    Storage::fake('local');
    foreach (['CAreadme.txt', 'CAlicense.txt', 'CAlicense4.txt'] as $file) {
        Storage::put("library/official/{$file}", 'text');
    }
    $this->release = PartRelease::factory()->create(['enabled' => true]);
    $previous = PartRelease::factory()->create(['enabled' => true]);
    $this->primitive = Part::factory()->official($this->release)->primitive()->create();
    // A diamond: the model reaches the primitive through two parts, so there are two paths to it
    $this->left = Part::factory()->official($previous)->inPartsFolder()->create();
    $this->right = Part::factory()->official($previous)->inPartsFolder()->create();
    $this->model = Part::factory()->official($previous)->inPartsFolder()->create();
    $this->unrelated = Part::factory()->official($previous)->inPartsFolder()->create();
    DB::table('related_parts')->insert([
        ['parent_id' => $this->left->id, 'subpart_id' => $this->primitive->id],
        ['parent_id' => $this->right->id, 'subpart_id' => $this->primitive->id],
        ['parent_id' => $this->model->id, 'subpart_id' => $this->left->id],
        ['parent_id' => $this->model->id, 'subpart_id' => $this->right->id],
    ]);
});

/** @return list<int> the ids of the parts CheckPart was queued for, sorted */
function checkedPartIds(): array
{
    return Queue::pushed(CheckPart::class)
        ->map(fn (CheckPart $job): int => (fn () => $this->parts->first()->id)->call($job))
        ->sort()
        ->values()
        ->all();
}

it('refreshes a released fix and every part using it exactly once', function () {
    Queue::fake([CheckPart::class, GeneratePartImage::class, UpdateRebrickable::class]);

    app(PostReleaseCleanup::class)->handle($this->release, [$this->primitive->id]);

    $expected = collect([$this->primitive->id, $this->left->id, $this->right->id, $this->model->id])->sort()->values()->all();
    expect(checkedPartIds())->toBe($expected)
        ->not->toContain($this->unrelated->id);
    Queue::assertPushedTimes(GeneratePartImage::class, 4);
});

it('refreshes a released part that is not a fix without the parts using it', function () {
    Queue::fake([CheckPart::class, GeneratePartImage::class, UpdateRebrickable::class]);

    app(PostReleaseCleanup::class)->handle($this->release);

    expect(checkedPartIds())->toBe([$this->primitive->id]);
    Queue::assertPushedTimes(GeneratePartImage::class, 1);
});
