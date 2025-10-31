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
        unset($lines[$input]);
        $text = implode("\n", $lines);
        expect($text)->toHaveCheckResult($expected, 'HasRequiredHeaderMeta');
    })->with([
        'missing description' => ['description', false],
        'missing name' => ['name', false],
        'missing name' => ['author', false],
        'missing type' => ['type', false],
        'missing license' => ['license', false],
        'nothing required missing' => ['cmdline', true],
    ]);

    test('check LibraryApprovedDescription', function (string $input, bool $expected) {
        $text = "0 {$input}\n0 Name: 123.dat";
        expect($text)->toHaveCheckResult($expected, 'LibraryApprovedDescription');
    })->with([
        'valid plain text description' => ["This Is A Test Description", true],
        'valid unicode description' => ["Some Chars are ෴ Approved ", true],
        'invalid unicode description' => ["Some Chars are \t not Approved ", false],
    ]);

    test('check PatternPartDescription', function (string $name, string $desc, bool $expected) {
        $text = "0 {$desc}\n0 Name: {$name}\n0 !LDRAW_ORG Unofficial_Part";
        expect($text)->toHaveCheckResult($expected, 'PatternPartDescription');
    })->with([
        'pattern with invalid description' => ["3001p01.dat", "Brick Test", false],
        'pattern with valid description' => ["3001p01.dat", "Brick Test with Pattern", true],
        'compound pattern with valid description' => ["3001p01c01.dat", "Brick Test with Pattern", true],
        'pattern with parenthetical after description' => ["3001p01.dat", "Brick Test with Pattern (Needs Work)", true],
        'excluded category' => ["3001p01.dat", "Sticker Test", true],
        'non-pattern' => ["3001s01.dat", "This Is A Test Description", true],
    ]);

    test('check AuthorInUsers', function (string $input, bool $expected) {
        $text = "0 Author: {$input}";
        expect($text)->toHaveCheckResult($expected, 'AuthorInUsers');
    })->with([
        'not in users' => ['Ole Kirk Christiansen [DaOGLego]', false],
        'only real name' => ['Test User', true],
        'only user name' => ['[TestUser]', true],
        'in users, both' => ['Test User [TestUser]', true],
        'in users, realname wrong' => ['Test Tool [TestUser]', true],
        'in users, userame wrong' => ['Test User [TestTool]', true],
    ]);

    test('check NameAndPartType', function (string $name, PartType $type, bool $expected) {
        $text = "0 Test\n0 Name: {$name}\n{$type->ldrawString(true)}";
        expect($text)->toHaveCheckResult($expected, 'NameAndPartType');
    })->with([
        'valid, no folder' => ['test.dat', PartType::Part, true],
        'valid, with folder' => ['s\\test.dat', PartType::Subpart, true],
        'invalid, no folder' => ['test.dat', PartType::Subpart, false],
        'invalid, with folder' => ['s\\test.dat', PartType::Primitive, false],
    ]);

    test('check DescriptionModifier', function (string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'DescriptionModifier');
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
        $text = "0 Test\n{$input->ldrawString(true)} " . PartTypeQualifier::Alias->value;
        expect($text)->toHaveCheckResult($expected, 'AliasInParts');
    })->with([
        'in parts folder' => [PartType::Part, true],
        'not in parts folder' => [PartType::Subpart, false],
    ]);

    test('check FlexibleSectionIsPart', function (PartType $input, bool $expected) {
        $text = "0 Test\n{$input->ldrawString(true)} " . PartTypeQualifier::FlexibleSection->value;
        expect($text)->toHaveCheckResult($expected, 'FlexibleSectionIsPart');
    })->with([
        'is part' => [PartType::Part, true],
        'not part' => [PartType::Shortcut, false],
    ]);

    test('check FlexibleHasCorrectSuffix', function (string $input, bool $expected) {
        $text = "0 Brick Test\n0 Name: {$input}\n". PartType::Part->ldrawString(true) . " " . PartTypeQualifier::FlexibleSection->value;
        expect($text)->toHaveCheckResult($expected, 'FlexibleHasCorrectSuffix');
    })->with([
        'correct suffix' => ['12345k01.dat', true],
        'incorrect, no suffix' => ['12345.dat', false],
        'incorrect, too many suffix chars' => ['12345k0100.dat', false],
    ]);

    test('check BfcIsCcw', function (string $input, bool $expected) {
        $text = "0 Brick Test\n0 BFC CERTIFY {$input}";
        expect($text)->toHaveCheckResult($expected, 'BfcIsCcw');
    })->with([
        'not approved CW' => ["CW", false],
        'not approved blank' => ["", false],
        'approved' => ["CCW", true],
    ]);

    test('check CategoryIsValid', function (string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'CategoryIsValid');
    })->with([
        'valid description category only' => ["0 " . PartCategory::Minifig->value . " Test\n0 Name: 123.dat\n0 !LDRAW_ORG Unofficial_Part", true],
        'valid category meta only' => ["0 Test\n0 Name: 123.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY " . PartCategory::Minifig->value, true],
        'valid both' => ["0 " . PartCategory::Minifig->value . " Test\n0 Name: 123.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY " . PartCategory::MinifigAccessory->value, true],
        'invalid description, no meta' => ["0 " . PartCategory::SheetCardboard->value . " Test\n0 Name: 123.dat\n0 !LDRAW_ORG Unofficial_Part", false],
        'not a part' => ["0 Test\n0 Name: 123.dat\n0 !LDRAW_ORG Unofficial_Primitive", true],
    ]);

    test('check part ObsoletePartIsValid', function (string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'ObsoletePartIsValid');
    })->with([
        'proper, end of description' => ["0 Test (Obsolete)\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY Obsolete", true],
        'proper, entire description' => ["0 ~Obsolete file\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY Obsolete", true],
        'proper, not in parts folder' => ["0 ~Obsolete file\n0 !LDRAW_ORG Unofficial_Primitive", true],
        'improper, no category' => ["0 Test (Obsolete)\n0 !LDRAW_ORG Unofficial_Part", false],
        'improper, wrong category' => ["0 Test (Obsolete)\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY Bar", false],
        'improper, wrong description' => ["0 Test\n0 !LDRAW_ORG Unofficial_Part\n0 !CATEGORY Obsolete", false],
    ]);

    test('check PatternHasSetKeyword', function (string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'PatternHasSetKeyword');
    })->with([
        'has set' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Set 1001", true],
        'has cmf' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS cmf", true],
        'has cmf with series' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS CMF Series 4", true],
        'has bam' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Build-A-Minifigure", true],
        'keyword missing' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Billund, LEGO House", false],
        'no keywords' => ["0 Brick Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Part", false],
        'not a pattern' => ["0 Brick Test\n0 Name: 3001.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Billund, LEGO House", true],
        'excluded category modulex' => ["0 Modulex Test\n0 Name: 3001.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Billund, LEGO House", true],
        'excluded category moved' => ["0 ~Moved to 3001p02\n0 Name: 3001.dat\n0 !LDRAW_ORG Unofficial_Part\n0 !KEYWORDS Billund, LEGO House", true],
        'not a part' => ["0 Test\n0 Name: 3001p01.dat\n0 !LDRAW_ORG Unofficial_Primitive", true],
    ]);

    test('test HistoryIsValid', function (string $input, bool $expected) {
       expect($input)->toHaveCheckResult($expected, 'HistoryIsValid');
    })->with([
        'invalid history, one line' => ["0 Test\n0 !HISTORY 2025-03-02 [] Test", false],
        'invalid history, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 [Test] Comment\n0 !HISTORY 202303-03 [Test] Comment2", false],
        'valid history, no lines' => ["0 Test\n0 Name: 3001.dat", true],
        'valid history, one line' => ["0 Test\n0 !HISTORY 2023-03-03 [Test] Comment\n", true],
        'valid history, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 [Test] Comment\n0 !HISTORY 2023-03-03 [Test] Comment2", true],
    ]);

    test('test HistoryUserIsRegistered', function (string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'HistoryUserIsRegistered');
    })->with([
        'invalid history username, one line' => ["0 Test\n0 !HISTORY 2025-03-02 [Invalid] Test", false],
        'invalid history username, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 [TestUser] Comment\n0 !HISTORY 2023-03-03 [Invalid] Comment2", false],
        'invalid history realname, one line' => ["0 Test\n0 !HISTORY 2025-03-02 {Not A User} Test", false],
        'invalid history realname, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 [TestUser] Comment\n0 !HISTORY 2023-03-03 {Not A User} Comment2", false],
        'invalid history username in realname format' => ["0 Test\n0 !HISTORY 2023-03-03 {TestUser} Comment", false],
        'valid history user, no lines' => ["0 Test\n0 Name: 3001.dat", true],
        'valid history username, one line' => ["0 Test\n0 !HISTORY 2023-03-03 [TestUser] Comment", true],
        'valid history username, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 [TestUser] Comment\n0 !HISTORY 2023-03-03 [TestUser2] Comment2", true],
        'valid history realname, one line' => ["0 Test\n0 !HISTORY 2023-03-03 {Test User} Comment\n", true],
        'valid history realname, multi line' => ["0 Test\n0 !HISTORY 2023-03-03 {Test User} Comment\n0 !HISTORY 2023-03-03 {Test User 2} Comment2", true],
    ]);

    test('check PreviewIsValid', function (?string $input, bool $expected) {
        expect($input)->toHaveCheckResult($expected, 'PreviewIsValid');
    })->with([
        'valid, default' => ["0 Test\n0 !PREVIEW 16 0 0 0 1 0 0 0 1 0 0 0 1", true],
        'valid, rotation' => ["0 Test\n0 !PREVIEW 16 0 0 0 0.70711 0 -0.70711 0.5 0.70711 0.5 0.5 -0.70711 0.5", true],
        'valid, missing' => ["0 Test\n0 Name: 3001.dat", true],
        'invalid color' => ["0 Test\n0 !PREVIEW 123456 0 0 0 1 0 0 0 1 0 0 0 1", false],
        'invalid, not enough arguments' => ["0 Test\n0 !PREVIEW 0 0 0 1 0 0 0 1 0 0 0 1", false],
        'invalid, non-number arguments' => ["0 Test\n0 !PREVIEW 16 0 a 0 1 0 0 0 1 0 0 0 1", false],
        'invalid, malformed number arguments' => ["0 Test\n0 !PREVIEW 16 0 0 .0-1 1 0 0 0 1 0 0 0 1", false],
        'invalid, singular matrix' => ["0 Test\n0 !PREVIEW 16 0 0 0 0 0 0 0 1 0 0 0 1", false],
        'invalid, negative matrix' => ["0 Test\n0 !PREVIEW 16 0 0 0 -1 0 0 0 -1 0 0 0 -1", false],
        'invalid, mirror matrix' => ["0 Test\n0 !PREVIEW 16 0 0 0 0 0 1 0 1 0 1 0 0", false],
        'invalid, shear matrix' => ["0 Test\n0 !PREVIEW 16 0 0 0 1 0 1 0 1 0 0 0 1", false],
        'invalid, scale matrix' => ["0 Test\n0 !PREVIEW 16 0 0 0 6 0 0 0 1 0 0 0 6", false],
    ]);

    test('check LibraryApprovedName', function (string $input, bool $expected) {
        $text = "0 Name: {$input}";
        expect($text)->toHaveCheckResult($expected, 'LibraryApprovedName');
    })->with([
        'valid' => ["test.dat", true],
        'valid with forward slash' => ["s\\1001.dat", true],
        'invalid' => ["!!.dat", false],
    ]);

    test('check NameFileNameMatch', function (string $name, string $filename, bool $expected) {
        $text = "0 Name: {$name}";
        expect($text)->toHaveCheckResult($expected, 'NameFileNameMatch', $filename);
    })->with([
        'match' => ['test.dat', 'test.dat', true],
        'match, with folder' => ['s\test.dat', 'test.dat', true],
        'no match' => ['test.dat', 'stest.dat', false],
    ]);

    test('check UnknownPartNumber', function (string $input, bool $expected) {
        $text = "0 Name: {$input}";
        expect($text)->toHaveCheckResult($expected, 'UnknownPartNumber');
    })->with([
        'not approved' => ['x999.dat', false],
        'approved' => ['u9999.dat', true],
    ]);

    test('check line allowed body meta', function (string $input, bool $expected) {
        $text = "0 Test\n0 Name: 123.dat\n0 BFC CERTIFY CCW\n{$input}";
        expect($text)->toHaveCheckResult($expected, 'ValidBodyMeta');
    })->with([
        'not approved' => ['0 WRITE blah blah', false],
        'approved Comment' => ['0 // blah blah blah', true],
        'unapproved Comment' => ['0 blah blah blah', false],
        'approved BFC' => ['0 BFC NOCLIP', true],
    ]);

    test('valid line', function (string $input, bool $expected) {
        $text = "0 Test\n0 Name: 123.dat\n0 BFC CERTIFY CCW\n{$input}";
        expect($text)->toHaveCheckResult($expected, 'ValidLines');
    })->with([
        'valid type 0' => ["0 // Free for comment 112341904.sfsfkajf", true],
        'valid type 0 empty' => ["0", false],
        'valid type 1' => ["1  16  0.01 -.01 1  10 0 0 0 1.0 0 0 0 10  test.dat", true],
        'valid type 2' => ["2  0x2123456  1 0.01 -0.01  1 0.23456789 -.12341234", true],
        'valid type 3' => ["3  16  1 0.01 -0.01  1 0.23456789 -.12341234  1 0 0", true],
        'valid type 4' => ["4  16  0 0 0  1 -.01 0  1 10 0  0 1.0 0", true],
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
        'invalid matrix' => ["1 16 0 0 0 1 0 0 0 0 0 0 0 1 test.dat", false],
        'invalid, identical points' => ["2 24 0 0 1.234 0 0 1.234", false],
        'invalid, identical points 1, 2' => ["3 16 0 0 0 0 0 0 1 1 0", false],
        'invalid, identical points 2, 3' => ["3 16 0 0 0 -1 1 0 -1 1 0", false],
        'invalid, identical points 3, 1' => ["3 16 0 0 0 -1 1 0 0 0 0", false],
        'invalid, angle too small 1, 2, 3' => ["3 16 1 0.0001 0 -1 0 0 1 0 0", false],
        'invalid, angle too small 2, 3, 1' => ["3 16 1 0 0 1 0.0001 0 -1 0 0", false],
        'invalid, angle too small 3, 1, 2' => ["3 16 -1 0 0 1 0 0 1 0.0001 0", false],
        'invalid, angle too large 1, 2, 3' => ["3 16 -1 0 0 1 0 0 2 0.0001 0", false],
        'invalid, angle too large 2, 3, 1' => ["3 16 2 0.0001 0 -1 0 0 1 0 0", false],
        'invalid, angle too large 3, 1, 2' => ["3 16 1 0 0 2 0.0001 0 -1 0 0", false],
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
        'invalid, identical line points' => ["5 24 0 1 0 0 1 0 1 1 0 0 1 1", false],
        'invalid, identical control points' => ["5 24 0 1 0 0 -1 0 0 1 1 0 1 1", false],
   ]);

    test('check NoSelfReference', function (string $input, bool $expected) {
        $text = "0 Test\n0 Name: test.dat\n0 BFC CERTIFY CCW\n{$input}";
        expect($text)->toHaveCheckResult($expected, 'NoSelfReference');
    })->with([
        'no circular reference, has subparts' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test3.dat\n1 16 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", true],
        'no circular reference, no subparts' => ["2 1 0 0 0 0 0\n2 2 0 0 0 0 0\n", true],
        'circular reference' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 16 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", false],
    ]);

});
