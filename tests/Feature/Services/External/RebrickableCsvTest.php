<?php

use App\Enums\RebrickableCsvFile;
use App\Services\External\RebrickableCsv;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = sys_get_temp_dir() . '/rebrickable-csv-test-' . uniqid();
    File::ensureDirectoryExists($this->dir);
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

it('streams rows keyed by header from CRLF files', function () {
    file_put_contents(
        "{$this->dir}/colors.csv",
        "id,name,rgb,is_trans\r\n0,Black,05131D,False\r\n-1,[Unknown],0033B2,False\r\n"
    );

    $rows = (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Colors)->all();

    expect($rows)->toBe([
        ['id' => '0', 'name' => 'Black', 'rgb' => '05131D', 'is_trans' => 'False'],
        ['id' => '-1', 'name' => '[Unknown]', 'rgb' => '0033B2', 'is_trans' => 'False'],
    ]);
});

it('handles doubled quotes, literal backslashes and empty values', function () {
    // Real rows from parts.csv, plus a quoted value ending in a backslash
    file_put_contents("{$this->dir}/parts.csv", <<<'CSV'
        part_num,name,part_cat_id,part_material
        2268c01pr0018,"Minidoll Hips and Cargo Pants with Nougat Legs, Dark Blue Shark Tattoo\ Print",64,Plastic
        3899pr0001,Equipment Cup / Mug with 'C:\' Print,27,Plastic
        x1,"Path ""C:\""",27,
        x2,"Ends in C:\",27,Plastic

        CSV);

    $rows = (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Parts)->all();

    expect($rows)->toHaveCount(4)
        ->and($rows[0]['name'])->toBe('Minidoll Hips and Cargo Pants with Nougat Legs, Dark Blue Shark Tattoo\ Print')
        ->and($rows[1]['name'])->toBe("Equipment Cup / Mug with 'C:\\' Print")
        ->and($rows[2]['name'])->toBe('Path "C:\"')
        ->and($rows[2]['part_material'])->toBeNull()
        ->and($rows[3]['name'])->toBe('Ends in C:\\');
});

it('reads gzipped files when the plain csv is missing', function () {
    file_put_contents("{$this->dir}/themes.csv.gz", gzencode("id,name,parent_id\n1,Technic,\n3,Competition,1\n"));

    $rows = (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Themes)->all();

    expect($rows)->toBe([
        ['id' => '1', 'name' => 'Technic', 'parent_id' => null],
        ['id' => '3', 'name' => 'Competition', 'parent_id' => '1'],
    ]);
});

it('skips blank lines', function () {
    file_put_contents("{$this->dir}/themes.csv", "id,name,parent_id\n1,Technic,\n\n3,Competition,1\n");

    expect((new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Themes)->count())->toBe(2);
});

it('throws when the file is missing', function () {
    (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Parts);
})->throws(FileNotFoundException::class);

it('throws on a row with the wrong number of columns', function () {
    file_put_contents("{$this->dir}/themes.csv", "id,name,parent_id\n1,Technic,\n3,Competition\n");

    (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Themes)->all();
})->throws(RuntimeException::class, 'Malformed row at line 3');

it('only reads as far as it is consumed', function () {
    file_put_contents("{$this->dir}/themes.csv", "id,name,parent_id\n1,Technic,\n3,Competition\n");

    $first = (new RebrickableCsv($this->dir))->rows(RebrickableCsvFile::Themes)->first();

    expect($first['name'])->toBe('Technic');
});

it('uses the configured directory by default', function () {
    config(['ldraw.rebrickable_csv_path' => $this->dir . '/']);
    file_put_contents("{$this->dir}/themes.csv", "id,name,parent_id\n1,Technic,\n");

    expect(app(RebrickableCsv::class)->rows(RebrickableCsvFile::Themes)->count())->toBe(1);
});
