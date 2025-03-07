<?php

use App\Enums\PartType;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function runSingleCheck(Part|ParsedPart $part, Check $check, ?string $filename = null): bool
{
    return count(app(\App\LDraw\Check\PartChecker::class)->singleCheck($part, $check)) == 0;
}

test('valid line', function (string $input, bool $expected) {
    $p = ParsedPart::fromArray([
        'body' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidLines()))->toBe($expected);
})->with([
    'valid type 0' => ["0 Free for comment 112341904.sfsfkajf", true],
    'valid type 0 empty' => ["0", true],
    'valid type 1' => ["1  1  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0  0 0 0  test.dat", true],
    'valid type 2' => ["2  0x2123456  1 0.01 -0.01  1 0.23456789 -.12341234", true],
    'valid type 3' => ["3  12  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", true],
    'valid type 4' => ["4  10001  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", true],
    'valid type 5' => ["5  1  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", true],
    'valid blank line' => ["", true],
    'invalid type 1' => ["1    0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0  0 0 0  test.dat", false],
    'invalid scientific notation' => ["2  1  1 0.01 -0.01  1e10 0.23456789 -.12341234", false],
    'invalid decimal number for color' => ["3  1.2  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", false],
    'invalid letter instead of number' => ["4  1  1 a -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", false],
    'invalid line type' => ["6  1  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", false],
]);

test('check library approved description', function (string $input, bool $expected) {
    $p = ParsedPart::fromArray([
        'description' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\LibraryApprovedDescription()))->toBe($expected);
})->with([
    'valid plain text description' => ["This Is A Test Decription", true],
    'valid unicode description' => ["Some Chars are ෴ Approved ", true],
    'invalid unicode description' => ["Some Chars are \xE2\x80\xA9 not Approved ", false],
    'empty' => ['', false],
]);


test('check description for pattern text', function (string $name, string $desc, bool $expected) {
    $p = ParsedPart::fromArray([
        'name' => $name,
        'type' => PartType::Part,
        'description' => $desc,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\PatternPartDesciption()))->toBe($expected);
})->with([
    'pattern with invalid description' => ["3001p01.dat", "This Is A Test Decription", false],
    'pattern with valid description' => ["3001p01.dat", "This Is A Test Decription with Pattern", true],
    'pattern with valid needs workdescription' => ["3001p01.dat", "This Is A Test Decription with Pattern (Needs Work)", true],
    'pattern with valid obsolete description' => ["3001p01.dat", "This Is A Test Decription with Pattern (Obsolete)", true],
    'non-pattern' => ["3001s01.dat", "This Is A Test Decription", true],
]);

test('check library approved name', function (string $input, bool $expected) {
    $p = ParsedPart::fromArray([
        'name' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\LibraryApprovedName()))->toBe($expected);
})->with([
    'valid' => ["test.dat", true],
    'valid with forward slash' => ["s\\1001.dat", true],
    'invalid' => ["!!.dat", false],
]);

test('check name and part type', function (string $name, PartType $type, bool $expected) {
    $p = ParsedPart::fromArray([
        'name' => $name,
        'type' => $type,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\NameAndPartType()))->toBe($expected);
})->with([
    'valid, no folder' => ['test.dat', PartType::Part, true],
    'valid, with folder' => ['s\\test.dat', PartType::Subpart, true],
    'invalid, no folder' => ['test.dat', PartType::Subpart, false],
    'invalid, with folder' => ['s\\test.dat', PartType::Primitive, false],
]);

test('check author in users', function () {
    $p = ParsedPart::fromArray([
        'username' => 'DaOGLego',
        'realname' => 'Ole Kirk Christiansen',
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AuthorInUsers()))->toBe(false);

    $u = User::factory()->create();
    $p->username = $u->name;
    $p->realname = $u->realname;
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AuthorInUsers()))->toBe(true);

    $p->username = '';
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AuthorInUsers()))->toBe(true);

    $p->username = $u->name;
    $p->realname = '';
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AuthorInUsers()))->toBe(true);
    $u->delete();
});

test('check library bfc certify', function (string $input, bool $expected) {
    $p = ParsedPart::fromArray([
        'bfc' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\BfcIsCcw()))->toBe($expected);
})->with([
    'not approved' => ["CW", false],
    'approved' => ["CCW", true],
]);

test('check pattern for set keyword', function (string $name, array $keywords, bool $expected) {
    $p = new ParsedPart();
    $p = ParsedPart::fromArray([
        'name' => $name,
        'keywords' => $keywords,
        'type' => PartType::Part,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\PatternHasSetKeyword()))->toBe($expected);
})->with([
    'has set' => ['3001p01.dat', ['keyword', 'set 1001'], true],
    'has cmf' => ['3001p01.dat', ['keyword', 'cmf'], true],
    'has cmf with series' => ['3001p01.dat', ['keyword', 'CMF Series 4'], true],
    'has bam' => ['3001p01.dat', ['keyword', 'build-a-minifigure'], true],
    'keyword missing' => ['3001p01.dat', ['keyword', 'keyword 2'], false],
    'not a pattern' => ['3001.dat', ['keyword', 'keyword 2'], true],
]);

test('check unknown part number', function (string $input, bool $expected) {
    $p = ParsedPart::fromArray([
        'name' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\UnknownPartNumber()))->toBe($expected);
})->with([
    'not approved' => ['x999.dat', false],
    'approved' => ['u9999.dat', true],
]);

test('check line allowed body meta', function (string $input, bool $expected) {
    $this->seed();
    $p = ParsedPart::fromArray([
        'body' => $input,
    ]);
    expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidBodyMeta()))->toBe($expected);
})->with([
    'not approved' => ['0 WRITE blah blah', false],
    'approved Comment' => ['0 // blah blah blah', true],
    'unapproved Comment' => ['0 blah blah blah', false],
    'approved BFC' => ['0 BFC NOCLIP', true],
]);

