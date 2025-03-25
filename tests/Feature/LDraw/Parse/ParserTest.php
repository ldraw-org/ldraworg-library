<?php

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\License;
use App\Enums\PartCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dosLineEndings', function (string $input, string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->dosLineEndings($input))->toBe($expected);
})->with([
    'unix style' => ["a\nb\nc\n", "a\r\nb\r\nc\r\n"],
    'mac style' => ["a\rb\rc\r", "a\r\nb\r\nc\r\n"],
    'windows style' => ["a\r\nb\r\nc\r\n", "a\r\nb\r\nc\r\n"],
    'mix of styles' => ["a\nb\rc\r\n", "a\r\nb\r\nc\r\n"],
]);

test('unixLineEndings', function (string $input, string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->unixLineEndings($input))->toBe($expected);
})->with([
    'unix style' => ["a\nb\nc\n", "a\nb\nc\n"],
    'mac style' => ["a\rb\rc\r", "a\nb\nc\n"],
    'windows style' => ["a\r\nb\r\nc\r\n", "a\nb\nc\n"],
    'mix of styles' => ["a\nb\rc\r\n", "a\nb\nc\n"],
]);

test('get description', function (string $input, ?string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getDescription($input))->toBe($expected);
})->with([
    'normal' => ["0 Test", "Test"],
    'multi-word' => ["0 Test Description", "Test Description"],
    'with line ending' => ["0 Test Description\n", "Test Description"],
    'multi-line' => ["0 Test Description\n0 Name: 12345.dat", "Test Description"],
    'unicode' => ["0 Tile 1 x 8 with Chinese \"长城\" (Great Wall) Pattern\n0 Name: 12345.dat\n", "Tile 1 x 8 with Chinese \"长城\" (Great Wall) Pattern"],
    'no 0' => ["Test", null],
    'blank 0' => ["0\n0 Name: 12345.dat", null],
    'empty file' => ["", null],
]);

test('get name', function (string $input, ?string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getName($input))->toBe($expected);
})->with([
    'normal' => ["0 Name: 123.dat", "123.dat"],
    'with folder' => ["0 Name: s\\123.dat", "s\\123.dat"],
    'with numeric folder' => ["0 Name: 48\\123.dat", "48\\123.dat"],
    'with line ending' => ["0 Name: 123.dat\n", "123.dat"],
    'multi-line' => ["0 Description\n0 Name: 123.dat\n0 Author: Test Author", "123.dat"],
    'blank' => ["0 Description\n0 Name:\n0 Author: Test Author", null],
    'no 0' => ["0 Description\nName: 123.dat\n", null],
    'empty file' => ["", null]
]);

test('get license', function (string $input, ?string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getLicense($input))->toBe($expected);
})->with([
    'normal, any text' => ["0 !LICENSE abcde", "abcde"],
    'normal, actual text' => ["0 !LICENSE Licensed under CC BY 4.0 : see CAreadme.txt", "Licensed under CC BY 4.0 : see CAreadme.txt"],
    'blank' => ["0 !LICENSE", null],
    'empty file' => ["", null],
    'multi-line' => ["0 Test\n0 !LICENSE abcde", "abcde"],
    'with line ending' => ["0 !LICENSE abcde\n", "abcde"],
]);

test('get cmd line', function (string $input, ?string $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getCmdLine($input))->toBe($expected);
})->with([
    'normal, any text' => ["0 !CMDLINE abcde", "abcde"],
    'normal, actual text' => ["0 !CMDLINE -c39", '-c39'],
    'blank' => ["0 !CMDLINE", null],
    'empty file' => ["", null],
    'multi-line' => ["0 Test\n0 !CMDLINE abcde", "abcde"],
    'with line ending' => ["0 !CMDLINE abcde\n", "abcde"],
]);

test('get meta category', function (string $input, ?PartCategory $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getMetaCategory($input))->toBe($expected);
})->with([
    'normal, actual one word text' => ["0 !CATEGORY Brick", PartCategory::Brick],
    'normal, actual multi-word text' => ["0 !CATEGORY Minifig Accessory", PartCategory::MinifigAccessory],
    'invalid category' => ["0 !CATEGORY abcde", null],
    'blank' => ["0 !CATEGORY", null],
    'empty file' => ["", null],
    'multi-line' => ["0 Test\n0 !CATEGORY Brick", PartCategory::Brick],
    'with line ending' => ["0 !CATEGORY Brick\n", PartCategory::Brick],
]);

test('get description category', function (string $input, ?PartCategory $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getDescriptionCategory($input))->toBe($expected);
})->with([
    'normal' => ["0 Brick Description", PartCategory::Brick],
    'with line ending' => ["0 Brick Description\n", PartCategory::Brick],
    'normal, with prefix' => ["0 ~Brick Description", PartCategory::Brick],
    'normal, with multiple prefixes' => ["0 ~|Brick Description", PartCategory::Brick],
    'normal, with prefix space' => ["0 ~| Brick Description", PartCategory::Brick],
    'with invalid category' => ["0 Test Description", null],
    'empty file' => ['', null],
]);

test('get author', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getAuthor($input))->toBe($expected);
})->with([
    'reaname, no username' => ["0 Author: Test", ['realname' => 'Test', 'user' => '']],
    'multiple word realname, no username' => ["0 Author: Test Test2 von Testington", ['realname' => 'Test Test2 von Testington', 'user' => '']],
    'username, no realname' => ["0 Author: [Test]", ['realname' => '', 'user' => 'Test']],
    'multiple word username, no realname' => ["0 Author: [Test Test2 von Testington]", null],
    'multiple word realname with username' => ["0 Author: Test Test2 von Testington [Test]", ['realname' => 'Test Test2 von Testington', 'user' => 'Test']],
    'multiple word realname with invalid username' => ["0 Author: Test Test2 von Testington [Test Jr]", null],
    'with line ending' => ["0 Author: Test Test2 von Testington [Test]\n", ['realname' => 'Test Test2 von Testington', 'user' => 'Test']],
    'multi-line' => ["0 Test\n0 Name: 123.dat\n0 Author: Test Test2 von Testington [Test]", ['realname' => 'Test Test2 von Testington', 'user' => 'Test']],
    'blank' => ["0 Author:", null],
    'empty file' => ["", null],
]);

test('get keywords', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getKeywords($input))->toBe($expected);
})->with([
    ["0 !KEYWORDS Comment", ['Comment']],
    ["0 !KEYWORDS Comment, Comment2", ['Comment', 'Comment2']],
    ["0 !CATEGORY Test\n0 !KEYWORDS Comment, Comment2\n", ['Comment', 'Comment2']],
    ["0 !KEYWORDS Comment With A Space, Comment2", ['Comment With A Space', 'Comment2']],
    ["0 !KEYWORDS Comment, Comment", ['Comment']],
    ["0 !KEYWORDS Comment, Comment2\n0 !KEYWORDS Comment, Comment2", ['Comment', 'Comment2']],
    ["0 !KEYWORDS Comment, Comment2\n0 !KEYWORDS Comment3, Comment4", ['Comment', 'Comment2', 'Comment3', 'Comment4']],
    ["0 !KEYWORDS", null],
    ["", null],
]);

test('get type', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getType($input))->toBe($expected);
})->with([
    'unofficial, no qualifier' => ["0 !LDRAW_ORG Unofficial_Part", ['unofficial' => true, 'type' => 'Part', 'qual' => null, 'releasetype' => null, 'release' => null]],
    'unofficial with qualifier' => ["0 !LDRAW_ORG Unofficial_Part Flexible_Section", ['unofficial' => true, 'type' => 'Part', 'qual' => 'Flexible_Section', 'releasetype' => null, 'release' => null]],
    'official update, no qualifier' => ["0 !LDRAW_ORG Part UPDATE 2022-01", ['unofficial' => false, 'type' => 'Part', 'qual' => '', 'releasetype' => 'UPDATE', 'release' => '2022-01']],
    'official update with qualifier' => ["0 !LDRAW_ORG Part Alias UPDATE 2022-01", ['unofficial' => false, 'type' => 'Part', 'qual' => 'Alias', 'releasetype' => 'UPDATE', 'release' => '2022-01']],
    'official original with qualifier' => ["0 !LDRAW_ORG Part Alias ORIGINAL", ['unofficial' => false, 'type' => 'Part', 'qual' => 'Alias', 'releasetype' => 'ORIGINAL', 'release' => 'original']],
    'with line ending' => ["0 !LDRAW_ORG Part UPDATE 2022-01\n", ['unofficial' => false, 'type' => 'Part', 'qual' => '', 'releasetype' => 'UPDATE', 'release' => '2022-01']],
    'multi-line' => ["0 Test\n0 !LDRAW_ORG Part UPDATE 2022-01", ['unofficial' => false, 'type' => 'Part', 'qual' => '', 'releasetype' => 'UPDATE', 'release' => '2022-01']],
    'blank' => ["0 !LDRAW_ORG", null],
    'official with invalid type' => ["0 !LDRAW_ORG Test", null],
    'unofficial with invalid type' => ["0 !LDRAW_ORG Unofficial_Test", null],
    'invalid release format' => ["0 !LDRAW_ORG Part UPDATE aaaa-bb", null],
    'invalid qualifier' => ["0 !LDRAW_ORG Part Test UPDATE 2022-01", null],
    'invalid release type' => ["0 !LDRAW_ORG Unofficial_Part RELEASE 2023-01", null],
    'empty file' => ["", null],
]);

test('get help', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getHelp($input))->toBe($expected);
})->with([
    'single statement' => ["0 !HELP Comment", ['Comment']],
    'multiple statement' => ["0 !HELP Comment\n0 !HELP Comment2", ['Comment', 'Comment2']],
    'blank statement' => ["0 !HELP", null],
    'blank statement with non-blank statement' => ["0 !HELP \n0 !HELP Comment2", ['Comment2']],
    'empty file' => ["", null],
]);

test('get bfc', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getBFC($input))->toBe($expected);
})->with([
    'cert' => ["0 BFC CERTIFY CCW", ['bfc' => 'CERTIFY', 'winding' => 'CCW']],
    'nocert' => ["0 BFC NOCERTIFY", ['bfc' => 'NOCERTIFY', 'winding' => '']],
    'noclip' => ["0 BFC NOCLIP", ['bfc' => 'NOCLIP', 'winding' => '']],
    'blank statement' => ["0 BFC", null],
    'empty file' => ["", null],
]);

test('get history', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getHistory($input))->toBe($expected);
})->with([
    'single statement' => ["0 !HISTORY 2023-03-03 [Test] Comment", [['date' => '2023-03-03', 'user' => 'Test', 'comment' => 'Comment']]],
    'multi-line' => ["0 Test\n0 !HISTORY 2023-03-03 [Test] Comment\n0 BFC CCW", [['date' => '2023-03-03', 'user' => 'Test', 'comment' => 'Comment']]],
    'synthetic user' => ["0 !HISTORY 2023-03-03 {Test} Comment", [['date' => '2023-03-03', 'user' => 'Test', 'comment' => 'Comment']]],
    'synthetic with space' => ["0 !HISTORY 2023-03-03 {Test User} Comment", [['date' => '2023-03-03', 'user' => 'Test User', 'comment' => 'Comment']]],
    'multiple statement' => ["0 !HISTORY 2023-03-03 [Test] Comment\n0 !HISTORY 2023-03-04 [Test2] Comment2", [
        ['date' => '2023-03-03', 'user' => 'Test', 'comment' => 'Comment'],
        ['date' => '2023-03-04', 'user' => 'Test2', 'comment' => 'Comment2']
    ]],
    'invalid date format' => ["0 !HISTORY 2023-0303 [Test] Comment", null],
    'invalid user format' => ["0 !HISTORY 2023-03-03 Test] Comment", null],
    'comment missing' => ["0 !HISTORY 2023-0303 [Test] ", null],
    'invalid with valid' => ["0 !HISTORY 2023-03 [Test] Comment\n0 !HISTORY 2023-03-04 [Test2] Comment2", [
        ['date' => '2023-03-04', 'user' => 'Test2', 'comment' => 'Comment2']
    ]],
    'blank statement' => ["0 !HISTORY", null],
    'empty file' => ["",  null],
]);

test('get subparts', function (string $input, ?array $expected) {
    expect(app(\App\LDraw\Parse\Parser::class)->getSubparts($input))->toBe($expected);
})->with([
    'single subpart' => ["0 Test\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", ['subparts' => ['test.dat'], 'textures' => null]],
    'no type 1 lines' => ["0 Test\n3 0 1 1 1 0 0 0 -1 -1 -1", null],
    'texmap planer' => ["0 !TEXMAP START PLANAR 1 2 3 1 2 3 1 2 3 test.png", ['subparts' => null, 'textures' => ['test.png']]],
    'texmap cylindrical' => ["0 !TEXMAP START CYLINDRICAL 1 2 3 1 2 3 1 2 3 4 test.png", ['subparts' => null, 'textures' => ['test.png']]],
    'texmap spherical' => ["0 !TEXMAP START SPHERICAL 1 2 3 1 2 3 1 2 3 4 5 test.png", ['subparts' => null, 'textures' => ['test.png']]],
    'texmap spherical with glossmap' => ["0 !TEXMAP START SPHERICAL 1 2 3 1 2 3 1 2 3 test.png GLOSSMAP test2.png", ['subparts' => null, 'textures' => ['test.png', 'test2.png']]],
    'texmap and type 1 lines' => [
        "1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat\n0 !TEXMAP START PLANAR 1 2 3 1 2 3 1 2 3 test.png GLOSSMAP test2.png",
        ['subparts' => ['test.dat', 'test2.dat'], 'textures' => ['test.png', 'test2.png']]
    ],
    'same subparts' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", ['subparts' => ['test.dat'], 'textures' => null]],
    'multiple subparts' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", ['subparts' => ['test.dat', 'test2.dat'], 'textures' => null]],
    'ldr and dat file' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.ldr\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", ['subparts' => ['test.ldr', 'test2.dat'], 'textures' => null]],
    'invlaid type 1 line' => ["0 Test\n 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", null],
]);

test('parse', function () {
    $text = file_get_contents(__DIR__ . "/testfiles/parsetest.dat");
    $part = app(\App\LDraw\Parse\Parser::class)->parse($text);
    expect($part->description)->toBe('Brick  1 x  2 x  5 with SW Han Solo Carbonite Pattern');
    expect($part->name)->toBe('2454aps5.dat');
    expect($part->realname)->toBe('Thomas Burger');
    expect($part->username)->toBe('grapeape');
    expect($part->unofficial)->toBe(true);
    expect($part->type)->toBe(PartType::Part);
    expect($part->type_qualifier)->toBe(PartTypeQualifier::Alias);
    expect($part->releasetype)->toBe(null);
    expect($part->release)->toBe(null);
    expect($part->license)->toBe(License::CC_BY_2);
    expect($part->help)->toBe(['This is help', 'This is more help']);
    expect($part->bfc)->toBe('CW');
    expect($part->metaCategory)->toBe(PartCategory::MinifigAccessory);
    expect($part->descriptionCategory)->toBe(PartCategory::Brick);
    expect($part->keywords)->toBe(['Bespin', 'Boba Fett', 'Cloud City', 'cold sleep', 'deep freeze', 'Set 7144']);
    expect($part->cmdline)->toBe('-c0');
    expect($part->history)->toBe([
        ['date' => '2003-08-01', 'user' => 'PTadmin', 'comment' => 'Official Update 2003-02'],
        ['date' => '2007-05-10', 'user' => 'PTadmin', 'comment' => 'Header formatted for Contributor Agreement'],
    ]);
    expect($part->subparts)->toBe([
        'subparts' => ['s\2454as01.dat'],
        'textures' => ['2454aps5.png']
    ]);
    expect($part->body . "\n")->toBe(app(\App\LDraw\Parse\Parser::class)->unixLineEndings(file_get_contents(__DIR__ . "/testfiles/getbody.dat")));
    expect($part->rawText)->toBe($text);

});

