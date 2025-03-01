<?php

use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\Part\Part;
use App\Models\User;


function testCheck(Check $check, ParsedPart|Part $part, ?string $filename = null): bool
{
    $errors = [];
    $fail = function (string $error) use ($errors) {
        $errors[] = $error;
    };
    $check->check($part, $fail);
    return count($errors) > 0;
}

test('valid line', function (string $input, bool $expected) {
    $p = new ParsedPart(
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        $input,
        null
    );
    expect(testCheck(new \App\LDraw\Check\Checks\ValidLines(), $p))->toBe($expected);
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
/*
test('check library approved description', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLibraryApprovedDescription($input))->toBe($expected);
})->with([
    'valid plain text description' => ["This Is A Test Decription", true],
    'valid unicode description' => ["Some Chars are ෴ Approved ", true],
    'invalid unicode description' => ["Some Chars are \xE2\x80\xA9 not Approved ", false],
    'empty' => ['', false],
]);


test('check description for pattern text', function (string $name, string $desc, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLibraryApprovedDescription($name, $desc))->toBe($expected);
})->with([
    'pattern with valid description' => ["3001p01.dat", "This Is A Test Decription with Pattern", true],
    'pattern with valid needs workdescription' => ["3001p01.dat", "This Is A Test Decription with Pattern (Needs Work)", true],
    'pattern with valid obsolete description' => ["3001p01.dat", "This Is A Test Decription with Pattern (Obsolete)", true],
    'non-pattern' => ["3001s01.dat", "This Is A Test Decription", true],
]);

test('check library approved name', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLibraryApprovedName($input))->toBe($expected);
})->with([
    'valid' => ["test.dat", true],
    'valid with forward slash' => ["s\\1001.dat", true],
    'invalid' => ["!!.dat", false],
]);

test('check name and part type', function (string $name, string $type, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkNameAndPartType($name, $type))->toBe($expected);
})->with([
    'valid, no folder' => ['test.dat', 'Part', true],
    'valid, with folder' => ['s\\test.dat', 'Subpart', true],
    'invalid, no folder' => ['test.dat', 'Subpart', false],
    'invalid, with folder' => ['s\\test.dat', 'Primitive', false],
]);

test('check author in users', function () {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkAuthorInUsers('DaOGLego', 'Ole Kirk Christiansen'))->toBe(false);
    $u = User::factory()->create();
    expect(app(\App\LDraw\Check\PartChecker::class)->checkAuthorInUsers($u->name, $u->realname))->toBe(true);
    expect(app(\App\LDraw\Check\PartChecker::class)->checkAuthorInUsers('', $u->realname))->toBe(true);
    expect(app(\App\LDraw\Check\PartChecker::class)->checkAuthorInUsers($u->name, ''))->toBe(true);
    $u->delete();
});

test('check library approved license', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLibraryApprovedLicense($input))->toBe($expected);
})->with([
    'not approved, not in db' => ["abcde", false],
    'not approved, in db' => ["Not redistributable : see NonCAreadme.txt", false],
    'approved' => ["Licensed under CC BY 4.0 : see CAreadme.txt", true],
]);

test('check library bfc certify', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLibraryBFCCertify($input))->toBe($expected);
})->with([
    'not approved' => ["CW", false],
    'approved' => ["CCW", true],
]);

test('check category', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkCategory($input))->toBe($expected);
})->with([
    'not approved' => ["Big Ugly Rock Piece", false],
    'approved' => ["Brick", true],
]);

test('check pattern for set keyword', function (string $name, array $keywords, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkPatternForSetKeyword($name, $keywords))->toBe($expected);
})->with([
    'has set' => ['3001p01.dat', ['keyword', 'set 1001'], true],
    'has cmf' => ['3001p01.dat', ['keyword', 'cmf'], true],
    'has cmf with series' => ['3001p01.dat', ['keyword', 'CMF Series 4'], true],
    'has bam' => ['3001p01.dat', ['keyword', 'build-a-minifigure'], true],
    'keyword missing' => ['3001p01.dat', ['keyword', 'keyword 2'], false],
    'not a pattern' => ['3001.dat', ['keyword', 'keyword 2'], true],
]);

test('check unknown part number', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkUnknownPartNumber($input))->toBe($expected);
})->with([
    'not approved' => ['x999.dat', false],
    'approved' => ['u9999.dat', true],
]);

test('check line allowed body meta', function (string $input, bool $expected) {
    expect(app(\App\LDraw\Check\PartChecker::class)->checkLineAllowedBodyMeta($input))->toBe($expected);
})->with([
    'not approved' => ['0 WRITE blah blah', false],
    'approved Comment' => ['0 // blah blah blah', true],
    'approved BFC' => ['0 BFC NOCLIP', true],
]);
*/
