<?php

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\LDraw\Check\Contracts\Check;
use App\LDraw\Parse\ParsedPart;
use App\Models\LdrawColour;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function runSingleCheck(Part|ParsedPart $part, Check $check, ?string $filename = null): bool
{
    return count(app(\App\LDraw\Check\PartChecker::class)->singleCheck($part, $check, $filename)) == 0;
}

beforeEach(function () {
    User::factory()->create([
        'name' => 'TestUser',
        'realname' => 'Test User'
    ]);
    User::factory()->create([
        'name' => 'TestUser2',
        'realname' => 'Test User 2'
    ]);
    LdrawColour::factory()->create([
        'code' => '16',
    ]);
    LdrawColour::factory()->create([
        'code' => '24',
    ]);
});

describe('part check', function () {
    test('has required header meta', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'description' => 'Test Description',
            'name' => '123.dat',
            'username' => 'TestUser',
            'realname' => 'Test User',
            'type' => PartType::Part,
            'license' => License::CC_BY_4,
        ]);

        if ($input == 'author') {
            $p->username = null;
            $p->realname = null;
        } else {
            $p->{$input} = null;
        }

        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\HasRequiredHeaderMeta()))->toBe($expected);
    })->with([
        'missing description' => ['description', false],
        'missing name' => ['name', false],
        'missing name' => ['author', false],
        'missing type' => ['type', false],
        'missing license' => ['license', false],
        'nothing required missing' => ['cmdline', true],
        'only username missing' => ['username', true],
        'only realname missing' => ['realname', true],
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

    test('check author in users', function (array $user, bool $expected) {
        $p = ParsedPart::fromArray($user);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AuthorInUsers()))->toBe($expected);

    })->with([
        'not in users' => [['realname' => 'Not A User', 'username' => 'NotAUser'], false],
        'in users, only realname' => [['realname' => 'Test User', 'username' => 'NotAUser'], true],
        'in users, only username' => [['realname' => 'Not A User', 'username' => 'TestUser'], true],
        'in users, both' => [['realname' => 'Test User', 'username' => 'TestUser'], true],
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

    test('check description modifier', function (array $values, bool $expected) {
        $p = ParsedPart::fromArray($values);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\DescriptionModifier()))->toBe($expected);
    })->with([
        'subpart, no tilde' => [[
            'description' => 'Test Description',
            'name' => 's\\1test.dat',
            'type' => PartType::Subpart,
        ], false],
        'subpart, tilde not first' => [[
            'description' => '=~Test Description',
            'name' => 's\\1test.dat',
            'type' => PartType::Subpart,
        ], false],
        'subpart, tilde only' => [[
            'description' => '~Test Description',
            'name' => 's\\1test.dat',
            'type' => PartType::Subpart,
        ], true],
        'subpart, tilde first' => [[
            'description' => '~=Test Description',
            'name' => 's\\1test.dat',
            'type' => PartType::Subpart,
        ], true],
        'alias, no equals' => [[
            'description' => 'Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'type_qualifier' => PartTypeQualifier::Alias
        ], false],
        'alias, equals' => [[
            'description' => '=Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'type_qualifier' => PartTypeQualifier::Alias
        ], true],
        'alias, equals with tilde' => [[
            'description' => '~=Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'type_qualifier' => PartTypeQualifier::Alias
        ], true],
        'moved, no tilde' => [[
            'description' => 'Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Moved,
        ], false],
        'moved, tilde not first' => [[
            'description' => '=~Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Moved,
        ], false],
        'moved, tilde' => [[
            'description' => '~Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Moved,
        ], true],
        'moved, tilde' => [[
            'description' => '~Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Moved,
        ], true],
        'obsolete category, no tilde' => [[
            'description' => 'Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Obsolete,
        ], false],
        'obsolete category, tilde' => [[
            'description' => '~Test Description',
            'name' => '1test.dat',
            'type' => PartType::Part,
            'metaCategory' => PartCategory::Obsolete,
        ], true],
        'obsolete description, no tilde' => [[
            'description' => 'Test Description (Obsolete)',
            'name' => '1test.dat',
            'type' => PartType::Part,
        ], false],
        'obsolete description, tilde' => [[
            'description' => '~Test Description (Obsolete)',
            'name' => '1test.dat',
            'type' => PartType::Part,
        ], true],
        'third party, no pipe' => [[
            'description' => 'Test Description',
            'name' => 't1000.dat',
            'type' => PartType::Part,
        ], false],
        'third party, pipe' => [[
            'description' => '|Test Description',
            'name' => 't1000.dat',
            'type' => PartType::Part,
        ], true],
        'third party, pipe with tilde' => [[
            'description' => '~|Test Description',
            'name' => 't1000.dat',
            'type' => PartType::Part,
        ], true],
    ]);

    test('check new part not phyical colour', function (?PartTypeQualifier $qual, bool $expected) {
        $p = ParsedPart::fromArray([
            'type_qualifier' => $qual,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\NewPartNotPhysicalColor()))->toBe($expected);
    })->with([
        'invalid' => [PartTypeQualifier::PhysicalColour, false],
        'valid' => [null, true],
    ]);

    test('check alias in parts', function (PartType $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'type' => $input,
            'type_qualifier' => PartTypeQualifier::Alias,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\AliasInParts()))->toBe($expected);
    })->with([
        'in parts folder' => [PartType::Part, true],
        'not in parts folder' => [PartType::Subpart, false],
    ]);

    test('check flexible section is part', function (PartType $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'type' => $input,
            'type_qualifier' => PartTypeQualifier::FlexibleSection,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\FlexibleSectionIsPart()))->toBe($expected);
    })->with([
        'is part' => [PartType::Part, true],
        'not part' => [PartType::Shortcut, false],
    ]);

    test('check flexible section has correct suffix', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => $input,
            'type_qualifier' => PartTypeQualifier::FlexibleSection,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\FlexibleHasCorrectSuffix()))->toBe($expected);
    })->with([
        'correct name' => ['12345k01.dat', true],
        'correct name, letter suffix' => ['12345kaa.dat', true],
        'incorrect, no suffix' => ['12345.dat', false],
        'incorrect, too many suffix chars' => ['12345k0100.dat', false],
    ]);

    test('check library bfc certify', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'bfc' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\BfcIsCcw()))->toBe($expected);
    })->with([
        'not approved' => ["CW", false],
        'approved' => ["CCW", true],
    ]);

    test('check category is valid', function (?PartCategory $desc, ?PartCategory $meta, bool $expected) {
        $p = ParsedPart::fromArray([
            'descriptionCategory' => $desc,
            'metaCategory' => $meta,
            'type' => PartType::Part,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\CategoryIsValid()))->toBe($expected);
    })->with([
        'valid descriptionCategory only' => [PartCategory::Brick, null, true],
        'valid metaCategory only' => [null, PartCategory::Brick, true],
        'valid both' => [PartCategory::Bar, PartCategory::Brick, true],
        'invalid' => [null, null, false],
    ]);

    test('check pattern for set keyword', function (string $name, array $keywords, bool $expected) {
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

    test('test history is valid', function (array $history, string $rawText, bool $expected) {
        $p = ParsedPart::fromArray([
            'history' => $history,
            'rawText' => $rawText
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\HistoryIsValid()))->toBe($expected);
    })->with([
        'invalid history, one line' => [[], "blah blah blah\n!HISTORY", false],
        'invalid history, multi line' => [[1, 2], "blah blah blah\n!HISTORY\n\n!HISTORY\n\!HISTORY", false],
        'valid history, no lines' => [[], "blah blah blah\nblah blah blah", true],
        'valid history, one line' => [[1], "blah blah blah\n!HISTORY", true],
        'valid history, multi line' => [[1, 2], "blah blah blah\n!HISTORY\n\n!HISTORY", true],
    ]);

    test('test history user is registered', function (array $history, bool $expected) {
        $p = ParsedPart::fromArray([
            'history' => $history,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\HistoryUserIsRegistered()))->toBe($expected);
    })->with([
        'invalid history username, one line' => [[['user' => 'NotAUser']], false],
        'invalid history username, multi line' => [[['user' => 'TestUser'], ['user' => 'NotAUser']], false],
        'invalid history realname, one line' => [[['user' => 'Not A User']], false],
        'invalid history realname, multi line' => [[['user' => 'Test User 2'], ['user' => 'Not A User']], false],
        'valid history user, no lines' => [[], true],
        'valid history username, one line' => [[['user' => 'TestUser']], true],
        'valid history username, multi line' => [[['user' => 'TestUser'], ['user' => 'TestUser2']], true],
        'valid history realname, one line' => [[['user' => 'Test User']], true],
        'valid history realname, multi line' => [[['user' => 'Test User'], ['user' => 'Test User 2']], true],
    ]);

    test('check preview is valid', function (?string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'preview' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\PreviewIsValid()))->toBe($expected);
    })->with([
        'valid, default' => ['16 0 0 0 1 0 0 0 1 0 0 0 1', true],
        'valid, missing' => [null, true],
        'invalid, not enough arguments' => ['0 0 0 1 0 0 0 1 0 0 0 1', false],
        'invalid, non-number arguments' => ['16 0 a 0 1 0 0 0 1 0 0 0 1', false],
        'invalid, malformed number arguments' => ['16 0 0 .0-1 1 0 0 0 1 0 0 0 1', false],
        'invalid, invalid matrix' => ['16 0 0 0 0 0 0 0 1 0 0 0 1', false],
        'invalid, nagative matrix' => ['16 0 0 0 -1 0 0 0 -1 0 0 0 -1', false],
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

    test('check name and filename match', function (string $name, string $filename, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => $name,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\NameFileNameMatch(), $filename))->toBe($expected);
    })->with([
        'match' => ['test.dat', 'test.dat', true],
        'match, with folder' => ['s\test.dat', 'test.dat', true],
        'no match' => ['test.dat', 'stest.dat', false],
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

    test('valid line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidLines()))->toBe($expected);
    })->with([
        'valid type 0' => ["0 // Free for comment 112341904.sfsfkajf", true],
        'valid type 0 empty' => ["0", true],
        'valid type 1' => ["1  16  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0  0 0 0  test.dat", true],
        'valid type 2' => ["2  0x2123456  1 0.01 -0.01  1 0.23456789 -.12341234", true],
        'valid type 3' => ["3  16  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", true],
        'valid type 4' => ["4  16  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", true],
        'valid type 5' => ["5  24  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", true],
        'valid blank line' => ["", true],
        'invalid type 1' => ["1  16  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0 0 0 0 0 test.dat", false],
        'invalid type 2' => ["2  24  0.01 -0.01 1  0.23456789 -.12341234 1  0", false],
        'invalid type 3' => ["3  16  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0 0", false],
        'invalid type 4' => ["4  16  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0 0 0 0 0", false],
        'invalid type 5' => ["5  24  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0 0 0 0 0", false],
        'invalid scientific notation' => ["2  16  1 0.01 -0.01  1e10 0.23456789 -.12341234", false],
        'invalid decimal number for color' => ["3  1.2  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", false],
        'invalid color code' => ["3  30000  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", false],
        'invalid color 24' => ["1  24  0.01 -0.01 1  0.23456789 -.12341234 1  0 0 0  0 0 0  test.dat", false],
        'invalid color 16' => ["2  16  1 0.01 -0.01  1 0.23456789 -.12341234", false],
        'invalid letter instead of number' => ["4  1  1 a -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", false],
        'invalid number' => ["4  16  1 1 .-01  1 0.23456789 -.12341234  1 0 0  0 0 0", false],
        'invalid line type' => ["6  16  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0  0 0 0", false],
    ]);

    test('valid type 1 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidType1Lines()))->toBe($expected);
    })->with([
        'valid' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", true],
        'valid, no type 1 lines' => ["2 24 0 0 0 0 0 1", true],
        'invalid matrix' => ["1 16 0 0 0 1 0 0 0 0 0 0 0 1 test.dat", false],
    ]);

    test('valid type 2 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidType2Lines()))->toBe($expected);
    })->with([
        'valid' => ["2 24 0 0 1.11 0 0 1.111", true],
        'valid, no type 2 lines' => ["3 16 -1 0 0 1 0 0 0 1 0", true],
        'invalid, identical points' => ["2 24 0 0 1.234 0 0 1.234", false],
    ]);

    test('valid type 3 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidType3Lines()))->toBe($expected);
    })->with([
        'invalid, identical points 1, 2' => ["3 16 0 0 0 0 0 0 1 1 0", false],
        'invalid, identical points 2, 3' => ["3 16 0 0 0 -1 1 0 -1 1 0", false],
        'invalid, identical points 3, 1' => ["3 16 0 0 0 -1 1 0 0 0 0", false],
        'invalid, angle too small 1, 2, 3' => ["3 16 1 0.0001 0 -1 0 0 1 0 0", false],
        'invalid, angle too small 2, 3, 1' => ["3 16 1 0 0 1 0.0001 0 -1 0 0", false],
        'invalid, angle too small 3, 1, 2' => ["3 16 -1 0 0 1 0 0 1 0.0001 0", false],
        'invalid, angle too large 1, 2, 3' => ["3 16 -1 0 0 1 0 0 2 0.0001 0", false],
        'invalid, angle too large 2, 3, 1' => ["3 16 2 0.0001 0 -1 0 0 1 0 0", false],
        'invalid, angle too large 3, 1, 2' => ["3 16 1 0 0 2 0.0001 0 -1 0 0", false],
        'valid' => ["3 16 -1 0 0 1 0 0 1 1 0", true],
        'valid, no type 2 lines' => ["2 24 0 0 1 0 0 0", true],
    ]);

    test('valid type 4 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidType4Lines()))->toBe($expected);
    })->with([
        'invalid, identical points 1, 2' => ["4 16 1 1 0 1 1 0 -1 -1 0 -1 1 0", false],
        'invalid, identical points 2, 3' => ["4 16 1 1 0 1 -1 0 1 -1 0 -1 1 0", false],
        'invalid, identical points 3, 4' => ["4 16 1 1 0 1 -1 0 -1 -1 0 -1 -1 0", false],
        'invalid, identical points 4, 1' => ["4 16 1 1 0 1 -1 0 -1 -1 0 1 1 0", false],
        'invalid, identical points 1, 3' => ["4 16 1 1 0 1 -1 0 1 1 0 -1 1 0", false],
        'invalid, identical points 4, 2' => ["4 16 1 1 0 1 -1 0 -1 -1 0 1 -1 0", false],
        'invalid, angle too large 1, 2, 3' => ["4 16 1 0 0 0 -0.0001 0 -1 0 0 0 1 0", false],
        'invalid, angle too large 2, 3, 4' => ["4 16 0 1 0 1 0 0 0 -0.0001 0 -1 0 0", false],
        'invalid, angle too large 3, 4, 1' => ["4 16 -1 0 0 0 1 0 1 0 0 0 -0.0001 0", false],
        'invalid, angle too large 4, 1, 2' => ["4 16 0 -0.0001 0 -1 0 0 0 1 0 1 0 0", false],
        'invalid, angle too small 1, 2, 3' => ["4 16 -0.001 0 0 0 15 0 0.001 0 0 0 -0.01 0", false],
        'invalid, angle too small 2, 3, 4' => ["4 16 0 -0.01 0 -0.001 0 0 0 15 0 0.001 0 0", false],
        'invalid, angle too small 3, 4, 1' => ["4 16 0.001 0 0 0 -0.01 0 -0.001 0 0 0 15 0", false],
        'invalid, angle too small 4, 1, 2' => ["4 16 0 15 0 0.001 0 0 0 -0.01 0 -0.001 0 0", false],
        'invalid, not coplaner 13' => ["4 16 1 1 0 1 -1 0 -1 -1 0 -1 1 1", false],
        'invalid, not coplaner 24' => ["4 16 1 1 0 1 -1 0 -1 -1 1 -1 1 0", false],
        'invalid, bowtie 1324' => ["4 16 -1 -1 0 1 1 0 -1 1 0 1 -1 0", false],
        'invalid, bowtie 1243' => ["4 16 -1 -1 0 -1 1 0 1 -1 0 1 1 0", false],
        'invalid, convex 13' => ["4 16 1 0 0 0 1 0 0.5 0 0 0 -1 0", false],
        'invalid, convex 24' => ["4 16 1 0 0 0 1 0 -1 0 0 0 0.5 0", false],
        'valid' => ["4 16 1 1 0 1 -1 0 -1 -1 0 -1 1 0", true],
        'valid, no type 2 lines' => ["2 24 0 0 1 0 0 0", true],
    ]);

    test('valid type 5 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\ValidType5Lines()))->toBe($expected);
    })->with([
        'valid' => ["5 24 0 1 0 0 -1 0 1 1 0 0 1 1", true],
        'valid, no type 2 lines' => ["3 16 -1 0 0 1 0 0 0 1 0", true],
        'invalid, identical line points' => ["5 24 0 1 0 0 1 0 1 1 0 0 1 1", false],
        'invalid, identical control points' => ["5 24 0 1 0 0 -1 0 0 1 1 0 1 1", false],
    ]);

    test('check no self reference', function (array $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => 'test.dat',
            'subparts' => ['subparts' => $input],
        ]);
        expect(runSingleCheck($p, new \App\LDraw\Check\Checks\NoSelfReference()))->toBe($expected);
    })->with([
        'no circular reference, has subparts' => [['test1.dat', 'test2.dat', 'test3.dat'], true],
        'no circular reference, no subparts' => [[], true],
        'circular reference' => [['test1.dat', 'test.dat', 'test3.dat'], false],
    ]);

});
