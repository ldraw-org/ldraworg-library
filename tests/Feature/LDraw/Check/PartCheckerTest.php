<?php

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Services\Check\Contracts\Check;
use App\Services\Check\PartChecker;
use App\Services\LDraw\Parse\ParsedPart;
use App\Models\LdrawColour;
use App\Models\User;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function passesCheck(ParsedPartCollection $part, Check $check, ?string $filename = null): bool
{
    $pc = app(PartChecker::class);
    $result = $pc->singleCheck($part, $check, $filename);

    return $result->isEmpty();
}

beforeEach(function () {
    User::factory()->create([
        'name' => 'TestUser',
        'realname' => 'Test User',
    ]);
    User::factory()->create([
        'name' => 'TestUser2',
        'realname' => 'Test User 2',
    ]);
    LdrawColour::factory()->create([
        'code' => '16',
    ]);
    LdrawColour::factory()->create([
        'code' => '24',
    ]);
});

describe('part check', function () {
    test('check HasRequiredHeaderMeta', function (string $input, bool $expected) {
        $lines = [
            'description' => '0 Test Description',
            'name' => '0 Name: 123.dat',
            'author' => '0 Author: Test User [TestUser]',
            'type' => '0 !LDRAW_ORG Unofficial_Part',
            'license' => License::CC_BY_4->ldrawString(),
        ];
        $file = new ParsedPartCollection(implode("\n", $lines));
        expect(passesCheck($file, new \App\Services\Check\PartChecks\HasRequiredHeaderMeta()))->toBe($expected);
    })->with([
        'missing description' => ['description', false],
        'missing name' => ['name', false],
        'missing name' => ['author', false],
        'missing type' => ['type', false],
        'missing license' => ['license', false],
        'nothing required missing' => ['cmdline', true],
    ]);

    test('check LibraryApprovedDescription', function (string $input, bool $expected) {
        $file = new ParsedPartCollection("0 {$input}\n0 Name: 123.dat");
        expect(passesCheck($file, new \App\Services\Check\PartChecks\LibraryApprovedDescription()))->toBe($expected);
    })->with([
        'valid plain text description' => ["This Is A Test Description", true],
        'valid unicode description' => ["Some Chars are ෴ Approved ", true],
        'invalid unicode description' => ["Some Chars are \t not Approved ", false],
    ]);

    test('check PatternPartDescription', function (string $name, string $desc, bool $expected) {
        $file = new ParsedPartCollection("0 {$desc}\n0 Name: {$name}\n0 !LDRAW_ORG Unofficial_Part");
        expect(passesCheck($file, new \App\Services\Check\PartChecks\PatternPartDescription()))->toBe($expected);
    })->with([
        'pattern with invalid description' => ["3001p01.dat", "Brick Test", false],
        'pattern with valid description' => ["3001p01.dat", "Brick Test with Pattern", true],
        'compound pattern with valid description' => ["3001p01c01.dat", "Brick Test with Pattern", true],
        'pattern with parenthetical after description' => ["3001p01.dat", "Brick Test with Pattern (Needs Work)", true],
        'excluded category' => ["3001p01.dat", "Sticker Test", true],
        'non-pattern' => ["3001s01.dat", "This Is A Test Description", true],
    ]);

    test('check AuthorInUsers', function (string $input, bool $expected) {
        $file = new ParsedPartCollection("0 Author: {$input}");
        expect(passesCheck($file, new \App\Services\Check\PartChecks\AuthorInUsers()))->toBe($expected);

    })->with([
        'not in users' => ['Ole Kirk Christiansen [DaOGLego]', false],
        'only real name' => ['Test User', true],
        'only user name' => ['[TestUser]', true],
        'in users, both' => ['Test User [TestUser]', true],
        'in users, realname wrong' => ['Test Tool [TestUser]', true],
        'in users, userame wrong' => ['Test User [TestTool]', true],
    ]);

    test('check NameAndPartType', function (string $name, PartType $type, bool $expected) {
        $file = new ParsedPartCollection("0 Test\n0 Name: {$name}\n{$type->ldrawString(true)}");
        expect(passesCheck($file, new \App\Services\Check\PartChecks\NameAndPartType()))->toBe($expected);
    })->with([
        'valid, no folder' => ['test.dat', PartType::Part, true],
        'valid, with folder' => ['s\\test.dat', PartType::Subpart, true],
        'invalid, no folder' => ['test.dat', PartType::Subpart, false],
        'invalid, with folder' => ['s\\test.dat', PartType::Primitive, false],
    ]);

    test('check DescriptionModifier', function (string $input, bool $expected) {
        $file = new ParsedPartCollection($input);
        expect(passesCheck($file, new \App\Services\Check\PartChecks\DescriptionModifier()))->toBe($expected);
    })->with([
        'subpart, no tilde' => [
            "0 Brick Test Description\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Subpart->ldrawString(true),
            false
        ],
        'subpart, tilde not first' => [
            "0 =~Brick Test Description\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Subpart->ldrawString(true),
            false
        ],
        'subpart, tilde only' => [
            "0 ~Brick Test Description\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Subpart->ldrawString(true),
            true
        ],
        'subpart, tilde first' => [
            "0 ~|Brick Test Description\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Subpart->ldrawString(true),
            true
        ],
        'alias, no equals' => [
            "0 Brick Test Description\n" .
            "0 Name: 1test.dat\n" . 
            PartType::Part->ldrawString(true) . ' ' . PartTypeQualifier::Alias->value,
            false
        ],
        'alias, equals' => [
            "0 =Brick Test Description\n" .
            "0 Name: 1test.dat\n" . 
            PartType::Part->ldrawString(true) . ' ' . PartTypeQualifier::Alias->value,
            true
        ],
        'alias, equals with tilde' => [
            "0 ~=Brick Test Description\n" .
            "0 Name: 1test.dat\n" . 
            PartType::Part->ldrawString(true) . ' ' . PartTypeQualifier::Alias->value,
            true
        ],
        'moved, no tilde' => [
            "0 Moved to 123\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Part->ldrawString(true),
            false
        ],
        'moved, tilde not first' => [
            "0 =~Moved to 123\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Part->ldrawString(true),
            false
        ],
        'moved, tilde' => [
            "0 ~Moved to 123\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Part->ldrawString(true),
            true
        ],
        'obsolete, no tilde' => [
            "0 Brick Test Description (Obsolete)\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Part->ldrawString(true) . "\n" .
            PartCategory::Obsolete->ldrawString(),
            false
        ],
        'obsolete, tilde' => [
            "0 ~Brick Test Description (Obsolete)\n" .
            "0 Name: s\\1test.dat\n" . 
            PartType::Part->ldrawString(true) . "\n" .
            PartCategory::Obsolete->ldrawString(),
            true
        ],
        'third party, no pipe' => [
            "0 Brick Test Description\n" .
            "0 Name: t1000.dat\n" . 
            PartType::Part->ldrawString(true),
            false
        ],
        'third party, pipe' => [
            "0 |Brick Test Description\n" .
            "0 Name: t1000.dat\n" . 
            PartType::Part->ldrawString(true),
            true
        ],
        'third party, pipe with tilde' => [
            "0 ~|Brick Test Description\n" .
            "0 Name: t1000.dat\n" . 
            PartType::Part->ldrawString(true),
            true
        ],
        'space after the prefix' => [
            "0 | Brick Test Description\n" .
            "0 Name: t1000.dat\n" . 
            PartType::Part->ldrawString(true),
            true
        ],
    ]);

    test('check AliasInParts', function (PartType $input, bool $expected) {
        $file = new ParsedPartCollection("0 Test\n{$input->ldrawString(true)} " . PartTypeQualifier::Alias->value);
        expect(passesCheck($file, new \App\Services\Check\PartChecks\AliasInParts()))->toBe($expected);
    })->with([
        'in parts folder' => [PartType::Part, true],
        'not in parts folder' => [PartType::Subpart, false],
    ]);

    test('check FlexibleSectionIsPart', function (PartType $input, bool $expected) {
        $file = new ParsedPartCollection("0 Test\n{$input->ldrawString(true)} " . PartTypeQualifier::FlexibleSection->value);
        expect(passesCheck($file, new \App\Services\Check\PartChecks\FlexibleSectionIsPart()))->toBe($expected);
    })->with([
        'is part' => [PartType::Part, true],
        'not part' => [PartType::Shortcut, false],
    ]);

    test('check FlexibleHasCorrectSuffix', function (string $input, bool $expected) {
        $file = new ParsedPartCollection("0 Brick Test\n0 Name: {$input}\n". PartType::Part->ldrawString(true) . " " . PartTypeQualifier::FlexibleSection->value);
        expect(passesCheck($file, new \App\Services\Check\PartChecks\FlexibleHasCorrectSuffix()))->toBe($expected);
    })->with([
        'correct suffix' => ['12345k01.dat', true],
        'letter suffix' => ['12345kaa.dat', true],
        'incorrect, no suffix' => ['12345.dat', false],
        'incorrect, too many suffix chars' => ['12345k0100.dat', false],
    ]);

    test('check library bfc certify', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'bfc' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\BfcIsCcw()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\CategoryIsValid()))->toBe($expected);
    })->with([
        'valid descriptionCategory only' => [PartCategory::Brick, null, true],
        'valid metaCategory only' => [null, PartCategory::Brick, true],
        'valid both' => [PartCategory::Bar, PartCategory::Brick, true],
        'invalid' => [null, null, false],
    ]);

    test('check part obsoleted properly', function (string $desc, ?PartCategory $meta, PartType $type, bool $expected) {
        $p = ParsedPart::fromArray([
            'description' => $desc,
            'metaCategory' => $meta,
            'type' => $type,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ObsoletePartIsValid()))->toBe($expected);
    })->with([
        'proper, end of description' => ['Test test test (Obsolete)', PartCategory::Obsolete, PartType::Part, true],
        'proper, entire description' => ['~Obsolete file', PartCategory::Obsolete, PartType::Part, true],
        'proper, not in parts folder' => ['Test', PartCategory::Obsolete, PartType::Subpart, true],
        'improper, no category' => ['Test test test (Obsolete)', null, PartType::Part, false],
        'improper, wrong category' => ['Test test test (Obsolete)', PartCategory::Animal, PartType::Part, false],
        'improper, wrong description' => ['Test test test', PartCategory::Obsolete, PartType::Part, false],
    ]);

    test('check pattern for set keyword', function (string $name, array $keywords, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => $name,
            'keywords' => $keywords,
            'type' => PartType::Part,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\PatternHasSetKeyword()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\HistoryIsValid()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\HistoryUserIsRegistered()))->toBe($expected);
    })->with([
        'invalid history username, one line' => [[['type' => '[', 'user' => 'NotAUser']], false],
        'invalid history username, multi line' => [[['type' => '[', 'user' => 'TestUser'], ['type' => '[', 'user' => 'NotAUser']], false],
        'invalid history realname, one line' => [[['type' => '{', 'user' => 'Not A User']], false],
        'invalid history realname, multi line' => [[['type' => '{', 'user' => 'Test User 2'], ['type' => '{', 'user' => 'Not A User']], false],
        'invalid history username in realname format' => [[['type' => '{', 'user' => 'TestUser']], false],
        'invalid history realname in username format' => [[['type' => '[', 'user' => 'Test User']], false],
        'valid history user, no lines' => [[], true],
        'valid history username, one line' => [[['type' => '[', 'user' => 'TestUser']], true],
        'valid history username, multi line' => [[['type' => '[', 'user' => 'TestUser'], ['type' => '[', 'user' => 'TestUser2']], true],
        'valid history realname, one line' => [[['type' => '{', 'user' => 'Test User']], true],
        'valid history realname, multi line' => [[['type' => '{', 'user' => 'Test User'], ['type' => '{', 'user' => 'Test User 2']], true],
    ]);

    test('check preview is valid', function (?string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'preview' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\PreviewIsValid()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\LibraryApprovedName()))->toBe($expected);
    })->with([
        'valid' => ["test.dat", true],
        'valid with forward slash' => ["s\\1001.dat", true],
        'invalid' => ["!!.dat", false],
    ]);

    test('check name and filename match', function (string $name, string $filename, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => $name,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\NameFileNameMatch(), $filename))->toBe($expected);
    })->with([
        'match' => ['test.dat', 'test.dat', true],
        'match, with folder' => ['s\test.dat', 'test.dat', true],
        'no match' => ['test.dat', 'stest.dat', false],
    ]);

    test('check unknown part number', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'name' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\UnknownPartNumber()))->toBe($expected);
    })->with([
        'not approved' => ['x999.dat', false],
        'approved' => ['u9999.dat', true],
    ]);

    test('check line allowed body meta', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidBodyMeta()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidLines()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidType1Lines()))->toBe($expected);
    })->with([
        'valid' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", true],
        'valid, no type 1 lines' => ["2 24 0 0 0 0 0 1", true],
        'invalid matrix' => ["1 16 0 0 0 1 0 0 0 0 0 0 0 1 test.dat", false],
    ]);

    test('valid type 2 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidType2Lines()))->toBe($expected);
    })->with([
        'valid' => ["2 24 0 0 1.11 0 0 1.111", true],
        'valid, no type 2 lines' => ["3 16 -1 0 0 1 0 0 0 1 0", true],
        'invalid, identical points' => ["2 24 0 0 1.234 0 0 1.234", false],
    ]);

    test('valid type 3 line', function (string $input, bool $expected) {
        $p = ParsedPart::fromArray([
            'body' => $input,
        ]);
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidType3Lines()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidType4Lines()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\ValidType5Lines()))->toBe($expected);
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
        expect(passesCheck($p, new \App\Services\Check\PartChecks\NoSelfReference()))->toBe($expected);
    })->with([
        'no circular reference, has subparts' => [['test1.dat', 'test2.dat', 'test3.dat'], true],
        'no circular reference, no subparts' => [[], true],
        'circular reference' => [['test1.dat', 'test.dat', 'test3.dat'], false],
    ]);

});
