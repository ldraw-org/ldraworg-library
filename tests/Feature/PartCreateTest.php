<?php

/*
use Illuminate\Http\Testing\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;

test('user and part id', function () {
    $user = \App\Models\User::firstWhere('name', 'OrionP');

    $post_vals = [
        'part_type_id' => 'a',
        'user_id' => 'a',
        'replace' => false,
        'partfix' => false,
        'partfile' => null,
    ];

    // Check part_type_id
    $response = $this->actingAs($user)->post('tracker/submit', $post_vals);
    $response->assertInvalid(['part_type_id', 'user_id', 'partfile']);
});

test('file extension', function () {
    $file = UploadedFile::fake()->create('test.mpd', 'text/plain');
    $user = \App\Models\User::firstWhere('name', 'OrionP');

    $post_vals = [
        'part_type_id' => 1,
        'user_id' => 1,
        'replace' => false,
        'partfix' => false,
        'partfile' => [$file],
    ];

    // Check part_type_id
    $response = $this->actingAs($user)->post('tracker/submit', $post_vals);
    $response->assertInvalid(['partfile.0' => 'The file extension is invalid (mpd)']);
});

test('file mime', function () {
    $user = \App\Models\User::firstWhere('name', 'OrionP');

    $part_type_id = 1;
    $user_id = $user->id;
    $replace = false;
    $partfix = false;
    $partfile = [UploadedFile::fake()->create('test.dat', 'image/png')];

    // Check part_type_id
    $response = $this->actingAs($user)->post('tracker/submit', compact('part_type_id', 'user_id', 'replace', 'partfix', 'partfile'));
    $response->assertInvalid(['partfile.0' => 'The file format is invalid (dat)']);

    $partfile = [UploadedFile::fake()->create('test.dat', 'image/jpeg')];
    $response = $this->actingAs($user)->post('tracker/submit', compact('part_type_id', 'user_id', 'replace', 'partfix', 'partfile'));
    $response->assertInvalid(['partfile.0' => 'The file format is invalid (dat)']);
});

function fileProvider() : array
{
    $values = str_replace("\r",'', file_get_contents(dirname(dirname(__DIR__)) . "/resources/testfiles/test_file.dat"));
    $values = explode("###\n", $values);
    $results = [];
    for($i = 0; $i < count($values); $i += 3) {
        $results[] = [trim($values[$i]), $values[$i+1], trim($values[$i+2])];
    }
    return $results;
}

test('file', function (string $filename, string $text, string $expected) {
    $user = \App\Models\User::firstWhere('name', 'OrionP');
    $part_type_id = 1;
    $user_id = -1;
    $replace = false;
    $partfix = false;

    Storage::fake('local')->put($filename, $text);
    $partfile = [new UploadedFile(Storage::disk('local')->path($filename), $filename, 'text/plain', null, true)];
    $response = $this->actingAs($user)->post('tracker/submit', compact('part_type_id', 'user_id', 'replace', 'partfix', 'partfile'));
    $response->assertInvalid(['partfile.0' => $expected]);
});

function headerProvider() : array
{
    $values = str_replace("\r",'', file_get_contents(dirname(dirname(__DIR__)) . "/resources/testfiles/test_header.dat"));
    $values = explode("###\n", $values);
    $results = [];
    for($i = 0; $i < count($values); $i += 3) {
        $results[] = [trim($values[$i]), $values[$i+1], trim($values[$i+2])];
    }
    return $results;
}

test('header', function (string $filename, string $text, string $expected) {
    $user = \App\Models\User::firstWhere('name', 'OrionP');
    $part_type_id = 1;
    $user_id = -1;
    $replace = false;
    $partfix = false;
    Storage::fake('local')->put($filename, $text);
    $partfile = [new UploadedFile(Storage::disk('local')->path($filename), $filename, 'text/plain', null, true)];
    $response = $this->actingAs($user)->post('tracker/submit', compact('part_type_id', 'user_id', 'replace', 'partfix', 'partfile'));
    $response->assertInvalid(['partfile.0' => $expected]);
});

function validfileProvider() : array
{
    $values = str_replace("\r",'', file_get_contents(dirname(dirname(__DIR__)) . "/resources/testfiles/test_valid_file.dat"));
    $values = explode("###\n", $values);
    $results = [];
    for($i = 0; $i < count($values); $i += 2) {
        $results[] = [trim($values[$i]), $values[$i+1]];
    }
    return $results;
}

test('valid file', function (string $filename, string $text) {
    $user = \App\Models\User::firstWhere('name', 'OrionP');
    $part_type_id = 1;
    $user_id = -1;
    $replace = false;
    $partfix = false;
    Storage::fake('local')->put($filename, $text);
    $partfile = [new UploadedFile(Storage::disk('local')->path($filename), $filename, 'text/plain', null, true)];
    $response = $this->actingAs($user)->post('tracker/submit', compact('part_type_id', 'user_id', 'replace', 'partfix', 'partfile'));
    $response->assertValid(['partfile.0']);
});
*/
