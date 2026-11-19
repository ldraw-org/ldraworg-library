<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Jobs\SyncRebrickableData;
use App\Models\LdrawColour;
use App\Models\Omr\Set;
use App\Models\Omr\Theme;
use App\Models\Part\Part;
use App\Models\RebrickablePart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** @return array<string, array<int, string>> */
function rebrickableDump(): array
{
    return [
        'themes' => ['id,name,parent_id', '1,Town,'],
        'colors' => ['id,name,rgb,is_trans,num_parts,num_sets,y1,y2', '4,Red,C91A09,False,1,1,1957,2027'],
        'color_mappings' => ['id,ext_system,ext_id,ext_descr,is_export_id,is_import_id', '4,LD,4,"[""Red""]",True,True'],
        'part_categories' => ['id,name', '11,Bricks'],
        'parts' => ['part_num,name,part_cat_id,part_material', '3001,Brick 2 x 4,11,Plastic', '3001pr0001,Brick 2 x 4 with Print,11,Plastic'],
        'part_mappings' => ['part_num,ext_system,ext_id,is_export_id,is_import_id', '3001pr0001,LD,3001p01,True,True', '3001pr0001,BL,3001pb01,True,True'],
        'part_relationships' => ['rel_type,child_part_num,parent_part_num', 'P,3001pr0001,3001'],
        'elements' => ['element_id,part_num,color_id,design_id', '300121,3001,4,3001'],
        'minifigs' => ['fig_num,name,num_parts,img_url', 'fig-000001,Figure,4,'],
        'sets' => ['set_num,name,year,theme_id,num_parts,img_url', '6000-1,New Name,1990,1,2,'],
        'set_mappings' => ['set_num,ext_system,ext_id', '6000-1,BL,6000-1'],
        'inventories' => ['id,version,set_num', '10,1,6000-1'],
        'inventory_parts' => ['inventory_id,part_num,color_id,quantity,is_spare,img_url', '10,3001pr0001,4,2,False,'],
        'inventory_minifigs' => ['inventory_id,fig_num,quantity', '99,fig-000001,1'],
        'inventory_sets' => ['inventory_id,set_num,quantity', '99,6000-1,1'],
    ];
}

beforeEach(function () {
    Cache::flush();
    Storage::fake();
    Http::preventStrayRequests();
    config(['ldraw.rebrickable_csv_url' => 'https://cdn.example.test/downloads/all_downloads.tar.gz']);
    fakeRebrickableCsv([]);
    LdrawColour::query()->delete();
    LdrawColour::factory()->create(['code' => 4, 'rebrickable_id' => 4]);
    Theme::create(['id' => 1, 'name' => 'Town']);
    $this->set = Set::create(['number' => '6000-1', 'name' => 'Old Name', 'year' => 1990, 'theme_id' => 1, 'rb_url' => '']);
    $this->part = Part::factory()->unofficial()->createQuietly([
        'filename' => 'parts/3001p01.dat',
        'description' => 'Brick 2 x 4 with Print',
        'type' => PartType::Part,
        'category' => PartCategory::Brick,
    ]);
});

it('downloads the CSVs and resyncs parts, links, inventories and set details', function () {
    $files = [];
    foreach (rebrickableDump() as $name => $lines) {
        $files["all_downloads/{$name}.csv"] = implode("\r\n", $lines) . "\r\n";
    }
    Http::fake(['cdn.example.test/downloads/all_downloads.tar.gz' => Http::response(rebrickableArchive($files))]);

    SyncRebrickableData::dispatchSync();

    $print = RebrickablePart::firstWhere('number', '3001pr0001');
    $this->part->refresh();
    expect($print->only(['ldraw', 'bricklink', 'print_of']))->toBe(['ldraw' => ['3001p01'], 'bricklink' => ['3001pb01'], 'print_of' => '3001'])
        ->and($this->part->rebrickable_part_id)->toBe($print->id)
        ->and($this->part->keywords->pluck('keyword')->sort()->values()->all())->toBe(['Bricklink 3001pb01', 'Rebrickable 3001pr0001'])
        ->and($this->set->fresh()->name)->toBe('New Name')
        ->and($this->set->rebrickable_parts()->get()->map(fn (RebrickablePart $p) => [$p->number, $p->pivot->quantity, $p->pivot->ldraw_colour_id])->all())
        ->toBe([['3001pr0001', 2, 4]]);
    Http::assertSentCount(1);
});

it('resyncs from the CSVs on disk without downloading', function () {
    fakeRebrickableCsv(rebrickableDump());

    SyncRebrickableData::dispatchSync(skipDownload: true);

    expect($this->set->fresh()->name)->toBe('New Name')
        ->and($this->part->fresh()->rebrickable_part_id)->not->toBeNull();
    Http::assertNothingSent();
});

it('stops before importing anything when the download fails', function () {
    fakeRebrickableCsv(rebrickableDump());
    Http::fake(['cdn.example.test/*' => Http::response('Payment Required', 402)]);

    expect(fn () => SyncRebrickableData::dispatchSync())->toThrow(RuntimeException::class);

    expect(RebrickablePart::count())->toBe(0)
        ->and($this->set->fresh()->name)->toBe('Old Name');
});
