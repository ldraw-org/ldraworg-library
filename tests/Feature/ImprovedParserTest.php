<?php

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Models\User;
use App\Services\Parser\ImprovedParser;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

describe('text clean', function () {
    it('changes to DOS style line endings', function (string $input, string $expected) {
        expect(ImprovedParser::dosLineEndings($input))
            ->toBe($expected);
    })->with([
        'unix style' => ["a\nb\nc\n", "a\r\nb\r\nc\r\n"],
        'mac style' => ["a\rb\rc\r", "a\r\nb\r\nc\r\n"],
        'windows style' => ["a\r\nb\r\nc\r\n", "a\r\nb\r\nc\r\n"],
        'mix of styles' => ["a\nb\rc\r\n", "a\r\nb\r\nc\r\n"],
    ]);

    it('changes to Unix style line endings', function (string $input, string $expected) {
        expect(ImprovedParser::unixLineEndings($input))
            ->toBe($expected);
    })->with([
        'unix style' => ["a\nb\nc\n", "a\nb\nc\n"],
        'mac style' => ["a\rb\rc\r", "a\nb\nc\n"],
        'windows style' => ["a\r\nb\r\nc\r\n", "a\nb\nc\n"],
        'mix of styles' => ["a\nb\rc\r\n", "a\nb\nc\n"],
    ]);
});

describe ('header and body split', function () {
    it ('parses body start line', function (string $input, int $start, string $header, string $body) {
        $file = new ParsedPartCollection($input);
        $start_line = $file->bodyStartLine();
        $header_text = $file->headerText();
        $body_text = $file->bodyText();
        expect($start_line)
            ->toBeInt()
            ->toBe($start)
            ->and($header_text)
            ->toBeString()
            ->toBe($header)
            ->and($body_text)
            ->toBeString()
            ->toBe($body);
    })->with([
        'empty file' => ['', 2, '', ''],
        'all lines with spaces' => [" \n \n \n", 2, '', ''],
        '1 liner with header meta' => ['0 Test Description', 2, '0 Test Description', ''],
        '1 liner with body meta' => ['0 BFC NOCLIP', 1, '', '0 BFC NOCLIP'],
        '1 liner with geometry' => ['1 16 0 0 0 1 0 0 0 0 1 0 0 1 empty.dat', 1, '', '1 16 0 0 0 1 0 0 0 0 1 0 0 1 empty.dat'],
        'header metas then geometry' => ["0 Test Description\n0 Name: 12345.dat\n1 16 0 0 0 1 0 0 0 0 1 0 0 1 empty.dat\n", 3, "0 Test Description\n0 Name: 12345.dat", '1 16 0 0 0 1 0 0 0 0 1 0 0 1 empty.dat'],
    ]);
});

describe('description parse', function () {
    it('passes description parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection($input);
        $description = $file->description();
        expect($description)
            ->toBeString()
            ->toBe($expected);
    })->with([
        'normal' => ["0 Test", "Test"],
        'multi-word' => ["0 Test Description", "Test Description"],
        'with line ending' => ["0 Test Description\n", "Test Description"],
        'multi-line' => ["0 Test Description\n0 Name: 12345.dat", "Test Description"],
        'unicode' => ["0 Tile 1 x 8 with Chinese \"长城\" (Great Wall) Pattern\n0 Name: 12345.dat\n", "Tile 1 x 8 with Chinese \"长城\" (Great Wall) Pattern"],
    ]);

    it('fails description parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $description = $file->description();
        expect($description)
            ->toBeNull();
    })->with([
        'first line official meta command' => "0 Name: 123.dat",
        'blank 0' => "0\n0 Name: 12345.dat",
        'empty file' => "",
    ]);

    it('passes description prefix parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection($input);
        $descriptionPrefix = $file->descriptionPrefix();
        expect($descriptionPrefix)
            ->toBeString()
            ->toBe($expected);
    })->with([
        'tilde' => ["0 ~Test", "~"],
        'pipe' => ["0 |Test", "|"],
        'equals' => ["0 =Test", "="],
        'underscore' => ["0 _Test", "_"],
        'with space' => ["0 _ Test", "_"],
        'combo' => ["0 =~Test", "=~"],
        'combo with space' => ["0 =~ Test", "=~"],
    ]);

    it('fails description prefix parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $descriptionPrefix = $file->descriptionPrefix();
        expect($descriptionPrefix)
            ->toBeNull();
    })->with([
        'no prefix' => "0 Test",
        'invalid prefix' => "0 @Test",
        'invalid with trailing valid' => "0 @~Test",
    ]);

});

describe('name parse', function () {
    it('passes name parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection("0 Name: {$input}");
        $name = $file->name();
        expect($name)
            ->toBeString()
            ->toBe($expected);
    })->with([
        'normal' => ["123.dat", "123.dat"],
        'with folder' => ["s\\123.dat", "s\\123.dat"],
        'with numeric folder' => ["48\\123.dat", "48\\123.dat"],
    ]);

    it('fails name parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $name = $file->name();
        expect($name)
            ->toBeNull();
    })->with([
        'blank' => "0 Name:",
    ]);

    it('passes basepart and no suffix parse', function (string $input, string $basepart) {
        $file = new ParsedPartCollection("0 Name: {$input}");
        $nameMeta = $file->getFirstMeta('name');
        expect($nameMeta)
        ->toBeArray()
        ->toHaveKeys(['basepart', 'suffixes'])
        ->basepart->toBeString()->toBe($basepart)
        ->suffixes->toBeNull();
    })->with([
        'unmodified name' => ['33333.dat', '33333'],
        'letter modifier' => ['33333a.dat', '33333a'],
        '2 letter modifier' => ['33333aa.dat', '33333aa'],
        'u prefix' => ['u33333.dat', 'u33333'],
        't prefix' => ['t33333.dat', 't33333'],
        's prefix' => ['s33333.dat', 's33333'],
    ]);

    it('fails basepart and no suffix parse', function (string $input) {
        $file = new ParsedPartCollection("0 Name: {$input}");
        $nameMeta = $file->getFirstMeta('name');
        expect($nameMeta)
        ->toBeArray()
        ->toHaveKeys(['basepart', 'suffixes'])
        ->basepart->toBeNull()
        ->suffixes->toBeNull();
    })->with([
        'all letters' => 'aaaaaaaa.dat',
        'letters in the middle' => '333sss3333.dat',
        'too many trailing letters' => '33333aaa.dat',
        'u prefix with letters' => 'uaaaaa.dat',
        't prefix with letters' => 'taaaaa.dat',
        's prefix with letters' => 'saaaaa.dat',
    ]);

    it('passes basepart with suffix parse', function (string $input, string $basepart, array $suffixes) {
        $file = new ParsedPartCollection("0 Name: {$input}");
        $nameMeta = $file->getFirstMeta('name');
        expect($nameMeta)
        ->toBeArray()
        ->toHaveKeys(['basepart', 'suffixes'])
        ->basepart->toBe($basepart)
        ->suffixes->toBe($suffixes);
    })->with([
        'pXX suffix' => ['33333p01.dat', '33333', ['p01']],
        'p[cd]XX suffix' => ['33333pc11.dat', '33333', ['pc11']],
        'pNNNN suffix' => ['33333p0100.dat', '33333', ['p0100']],
        'cXX suffix' => ['33333c01.dat', '33333', ['c01']],
        'dXX suffix' => ['33333d01.dat', '33333', ['d01']],
        'dXX suffix' => ['33333k01.dat', '33333', ['k01']],
        '-fN suffix' => ['33333-f1.dat', '33333', ['-f1']],
        'multiple' => ['33333p0100c01d01k01.dat', '33333', ['p0100', 'c01', 'd01', 'k01']],
        'multiple with -f1' => ['33333p0100c01d01k01-f1.dat', '33333', ['p0100', 'c01', 'd01', 'k01', '-f1']],
        'letter modifier with suffix' => ['33333cc01.dat', '33333c', ['c01']],
        '2 letter modifier with suffix' => ['33333bcc01.dat', '33333bc', ['c01']],
    ]);

    it('passes basepart but fails suffix parse', function (string $input, string $basepart, string $suffixes) {
        $file = new ParsedPartCollection("0 Name: {$input}");
        $nameMeta = $file->getFirstMeta('name');
        expect($nameMeta)
        ->toBeArray()
        ->toHaveKeys(['basepart', 'suffixes', 'suffixes_invalid'])
        ->basepart->toBe($basepart)
        ->suffixes->toBe($suffixes)
        ->suffixes_invalid->toBeTrue();
    })->with([
        'pNNNN not numbers' => ['33333paaaa.dat', '33333', "paaaa"],
    ]);

});

describe('author parse', function () {
    beforeEach(function () {
        User::factory()->create([
            'name' => 'TestUser',
            'realname' => 'Test User',
        ]);
    });

    it('passes author parse', function (string $input, ?string $realname, ?string $username) {
        $file = new ParsedPartCollection("0 Author: {$input}");
        expect($file->author())
            ->toBeArray()
            ->toHaveKeys(['realname', 'username'])
            ->username->toBe($username)
            ->realname->toBe($realname);
    })->with([
        'realname, no username' => ["Test", 'Test', null],
        'multiple word realname, no username' => ["Test Test2 von Testington", 'Test Test2 von Testington', null],
        'username, no realname' => ["[Test]", null, 'Test'],
        'multiple word realname with username' => ["Test Test2 von Testington [Test]", 'Test Test2 von Testington', 'Test'],
    ]);

    it('fails author parse', function (string $input) {
        $file = new ParsedPartCollection("0 Author: {$input}");
        expect($file->author())
            ->toBeNull();
    })->with([
        'invalid username, no realname' => "[Test Test2 von Testington]",
        'valid realname with invalid username' => "Test Test2 von Testington [Test Jr]",
        'blank' => "",
    ]);

    it('passes author user lookup', function (string $input) {
        $file = new ParsedPartCollection("0 Author: {$input}");
        $user = User::firstWhere('name', 'TestUser');
        expect($file->authorUser())
            ->id->toBe($user->id);
    })->with([
        'both real and user names' => 'Test User [TestUser]',
        'correct real and incorrect user name' => 'Test User [TestUse]',
        'correct user and incorrect real name' => 'Test Use [TestUser]',
    ]);

    it('fails author user lookup', function (string $input) {
        $file = new ParsedPartCollection("0 Author: {$input}");
        expect($file->authorUser())
            ->toBeNull();
    })->with([
        'Ole Kirk Christiansen [DaOGLego]',
    ]);

});

describe('ldraworg parse', function () {
    it('fails ldraworg parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type = $file->type();
        expect($type)->toBeNull();
    })->with([
        'blank' => "0 !LDRAW_ORG",
        'official with invalid type' => "0 !LDRAW_ORG Test",
        'unofficial with invalid type' => "0 !LDRAW_ORG Unofficial_Test",
        'official with invalid type qualifier' => "0 !LDRAW_ORG Test Test",
        'unofficial with invalid type qualifier' => "0 !LDRAW_ORG Unofficial_Test Test",
        'invalid release format' => "0 !LDRAW_ORG Part UPDATE aaaa-bb",
        'invalid release type' => "0 !LDRAW_ORG Unofficial_Part RELEASE 2023-01",
        'empty file' => "",
    ]);


    it('passes type parse', function (string $input, PartType $expected) {
        $file = new ParsedPartCollection($input);
        $type = $file->type();
        expect($type)->toBe($expected);
    })->with([
        'unofficial, no qualifier' => ["0 !LDRAW_ORG Unofficial_Part", PartType::Part],
        'unofficial with qualifier' => ["0 !LDRAW_ORG Unofficial_Part Flexible_Section", PartType::Part],
        'official update, no qualifier' => ["0 !LDRAW_ORG Part UPDATE 2022-01", PartType::Part],
        'official update with qualifier' => ["0 !LDRAW_ORG Part Alias UPDATE 2022-01", PartType::Part],
        'official original with no qualifier' => ["0 !LDRAW_ORG Part ORIGINAL", PartType::Part],
        'official original with qualifier' => ["0 !LDRAW_ORG Part Alias ORIGINAL", PartType::Part],
    ]);

    it('passes type qualifier parse', function (string $input, PartTypeQualifier $expected) {
        $file = new ParsedPartCollection($input);
        $type_qualifier = $file->type_qualifier();
        expect($type_qualifier)->toBe($expected);
    })->with([
        'unofficial with qualifier' => ["0 !LDRAW_ORG Unofficial_Part Flexible_Section", PartTypeQualifier::FlexibleSection],
        'official update with qualifier' => ["0 !LDRAW_ORG Part Alias UPDATE 2022-01", PartTypeQualifier::Alias],
        'official original with qualifier' => ["0 !LDRAW_ORG Part Alias ORIGINAL", PartTypeQualifier::Alias],
    ]);
});

describe('license parse', function () {
    it('passes license parse', function ($license) {
        expect($license)
            ->toBeInstanceOf(License::class);
    })->with(function (): \Generator {
        foreach (License::cases() as $license) {
            $file = new ParsedPartCollection($license->ldrawString());
            $license = $file->license();
            yield $license;
        }
    });

    it('fail license parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $license = $file->license();
        expect($license)->toBeNull();
    })->with([
        'normal, invalid text' => "0 !LICENSE abcde",
        'blank' => "0 !LICENSE",
        'empty file' => "",
    ]);
});

describe('help parse', function () {
    it('passes help parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $help = $file->help();
        expect($help)->toBe($expected);
    })->with([
        'single statement' => ["0 !HELP Comment", ['Comment']],
        'multiple statement' => ["0 !HELP Comment\n0 !HELP Comment2", ['Comment', 'Comment2']],
        'blank statement with non-blank statement' => ["0 !HELP \n0 !HELP Comment2", ['Comment2']],
    ]);

    it('fails help parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $help = $file->help();
        expect($help)->toBeNull();
    })->with([
        'blank statement' => "0 !HELP",
        'multiple blank statements' => "0 !HELP   \n0 !HELP \n0 !HELP\n",
        'empty file' => "",
    ]);
});


describe('bfc parse', function () {
    it('passes bfc parse', function (string $input, bool $inHeader, string $command, ?string $winding) {
        $file = new ParsedPartCollection($input);
        $bfc = Arr::get($file->bfc() ?? [], 0);
        expect($bfc)
            ->toBeArray()
            ->toHaveKeys(['line_number', 'in_header', 'command', 'winding'])
            ->in_header->toBeBool()->toBe($inHeader)
            ->command->toBeString->toBe($command)
            ->winding->toBe($winding);
    })->with([
        'cert' => ["0 BFC CERTIFY", true, 'CERTIFY', null],
        'cert, ccw' => ["0 BFC CERTIFY CCW", true, 'CERTIFY', 'CCW'],
        'cert, cw' => ["0 BFC CERTIFY CW", true, 'CERTIFY', 'CW'],
        'nocert' => ["0 BFC NOCERTIFY", true, 'NOCERTIFY', null],
        'clip' => ["0 BFC CLIP", false, 'CLIP', null],
        'clip ccw' => ["0 BFC CLIP CCW", false, 'CLIP', 'CCW'],
        'clip cw' => ["0 BFC CLIP CW", false, 'CLIP', 'CW'],
        'noclip' => ["0 BFC NOCLIP", false, 'NOCLIP', null],
        'cw' => ["0 BFC CW", false, 'CW', null],
        'ccw' => ["0 BFC CCW", false, 'CCW', null],
        'invert' => ["0 BFC INVERTNEXT", false, 'INVERTNEXT', null],
    ]);

    it('fails bfc parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $bfc = Arr::get($file->bfc() ?? [], 0);
        expect($bfc)
            ->toBeNull();
    })->with([
        'invalid command' => "0 BFC YES",
        'invalid winding' => "0 BFC CERTIFY LEFT",
        'non-winding command with winding' => "0 BFC NOCLIP CCW",
        'blank statement' => "0 BFC",
        'empty file' => "",
    ]);

    it('passes header bfc parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection($input);
        $bfc = $file->headerBfc();
        expect($bfc)->toBe($expected);
    })->with([
        'cert, ccw' => ["0 BFC CERTIFY CCW", 'CCW'],
        'cert, cw' => ["0 BFC CERTIFY CW", 'CW'],
        'nocert' => ["0 BFC NOCERTIFY", 'none'],
    ]);

    test('fails header bfc parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $bfc = $file->headerBfc();
        expect($bfc)->toBeNull;
    })->with([
        'non-header bfc' => "0 BFC NOCLIP",
        'inside body' => "1 16 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat\n0 BFC CERTIFY CW\n1 16 0 0 0 1 0 0 0 1 0 0 0 1 empty.dat",
    ]);
});

describe('category parse', function () {
    it('passes category parse', function (string $input, PartCategory $expected) {
        $file = new ParsedPartCollection($input);
        $category = $file->category();
        expect($category)
            ->toBe($expected);
    })->with([
        'via description, actual one word text' => ["0 Brick Description\n", PartCategory::Brick],
        'via meta, actual one word text' => ["0 Test Description\n0 !CATEGORY Brick", PartCategory::Brick],
        'via meta, actual multi-word text' => ["0 Test Description\n0 !CATEGORY Minifig Accessory", PartCategory::MinifigAccessory],
        'via meta, valid description word' => ["0 Brick Description\n0 !CATEGORY Minifig Accessory", PartCategory::MinifigAccessory],
    ]);

    it('fails category parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $category = $file->category();
        expect($category)->toBeNull();
    })->with([
        'via description, valid multi word but no meta' => "0 Sheet Cardboard Description\n",
        'invalid category, valid description word' => "0 Brick Description\n0 !CATEGORY abcde",
        'invalid category, both' => "0 Test Description\n0 !CATEGORY abcde",
        'blank' => "0 !CATEGORY",
        'empty file' => "",
    ]);
});


describe('keyword parse', function () {
    it('passes keyword parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $keywords = $file->keywords();
        expect($keywords)
            ->toBe($expected);
    })->with([
        'one word' => ["0 !KEYWORDS Comment", ['Comment']],
        'multiple words' => ["0 !KEYWORDS Comment, Comment2", ['Comment', 'Comment2']],
        'words with a space' => ["0 !KEYWORDS Comment With A Space, Comment2", ['Comment With A Space', 'Comment2']],
        'words with quotes' => ["0 !KEYWORDS \"Quoted Comment\", Comment2", ['"Quoted Comment"', 'Comment2']],
        'extra commas' => ["0 !KEYWORDS Comment,    , Comment2", ['Comment', 'Comment2']],
        'identical words' => ["0 !KEYWORDS Comment, Comment", ['Comment']],
        'multiple lines, identical words' => ["0 !KEYWORDS Comment, Comment2\n0 !KEYWORDS Comment, Comment2", ['Comment', 'Comment2']],
        'multiple lines, different words' =>["0 !KEYWORDS Comment, Comment2\n0 !KEYWORDS Comment3, Comment4", ['Comment', 'Comment2', 'Comment3', 'Comment4']],
    ]);
    it('fails keyword parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $keywords = $file->keywords();
        expect($keywords)->toBeNull();
    })->with([
        'blank' => "0 !KEYWORDS",
        'empty file' => "",
    ]);

});

describe('keyword parse', function () {
    it('passes cmdline parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection($input);
        $cmdline = $file->cmdline();
        expect($cmdline)->toBe($expected);
    })->with([
        'normal, any text' => ["0 !CMDLINE abcde", "abcde"],
        'normal, actual text' => ["0 !CMDLINE -c39", '-c39'],
    ]);

    it('fails cmdline parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $cmdline = $file->cmdline();
        expect($cmdline)->toBeNull();
    })->with([
        'blank' => "0 !CMDLINE",
        'empty file' => "",
    ]);
});

describe('preview parse', function () {
    it('passes preview parse', function (string $input, string $expected) {
        $file = new ParsedPartCollection($input);
        $preview = $file->preview();
        expect($preview)->toBe($expected);
    })->with([
        'valid' => ["0 !PREVIEW 16 0 0 0 1 0 0 0 1 0 0 0 1", "16 0 0 0 1 0 0 0 1 0 0 0 1"],
        'valid with floats' => ["0 !PREVIEW 16 0.8 .7 -1.0 -.8 -10 0 0 1 0 0 0 1", "16 0.8 .7 -1.0 -.8 -10 0 0 1 0 0 0 1"],
    ]);

    it('fails preview parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $preview = $file->preview();
        expect($preview)->toBeNull();
    })->with([
        'invalid, not enough numbers' => "0 !PREVIEW 16 0 0 0 1 0 0 0 0 0 0 1",
        'invalid, hex for color' => "0 !PREVIEW 0x2121212 0 0 0 1 0 0 0 0 0 0 1",
        'invalid, invalid number' => "0 !PREVIEW 16 0 0 0 1e1 0 0 0 0 0 0 1",
        'blank' => "0 !PREVIEW",
        'empty file' => "",
    ]);
});

describe('history parse', function () {
    it('passes history parse', function (string $input, string $date, ?string $realname, ?string $username, string $comment) {
        $file = new ParsedPartCollection($input);
        $history = Arr::get($file->history(), 0);
        expect($history)
            ->toBeArray()
            ->toHaveKeys(['date', 'realname', 'username', 'comment'])
            ->date->toBeString()->toBe($date)
            ->realname->toBe($realname)
            ->username->toBe($username)
            ->comment->toBeString->toBe($comment);
    })->with([
        'single statement' => ["0 !HISTORY 2023-03-03 [Test] Comment", '2023-03-03', null, 'Test', 'Comment'],
        'synthetic user' => ["0 !HISTORY 2023-03-03 {Test} Comment", '2023-03-03', 'Test', null, 'Comment'],
        'synthetic with space' => ["0 !HISTORY 2023-03-03 {Test User} Comment", '2023-03-03', 'Test User', null, 'Comment'],
    ]);

    it('fails history parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $history = $file->history();
        expect($history)->toBeNull();
    })->with([
        'invalid date format' => "0 !HISTORY 2023-0303 [Test] Comment",
        'invalid user format' => "0 !HISTORY 2023-03-03 Test] Comment",
        'comment missing' => "0 !HISTORY 2023-0303 [Test] ",
        'blank statement' => "0 !HISTORY",
        'empty file' => "",
    ]);

    it('has valid history', function (string $input, bool $expected) {
        $file = new ParsedPartCollection($input);
        $historyInvalid = $file->hasInvalidHistory();
        expect($historyInvalid)->toBeBool()->toBe($expected);
    })->with([
        'no invalid' => ["0 !HISTORY 2023-03-03 [Test] Comment\n0 !HISTORY 2023-03-03 [Test] Comment2", false],
        'valid with invalid' => ["0 !HISTORY 2023-03-03 [Test] Comment\n0 !HISTORY 202303-03 [Test] Comment2", true],
        'all invalid' => ["0 !HISTORY 2023-03-03 Test] Comment\n0 !HISTORY 202303-03 [Test] Comment2", true],
        'no history' => ["0 Test File", false],
    ]);

});

describe('type 1 parse', function () {
    it('passes type 1 parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype','color', 'x1', 'y1', 'z1', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'file'])
            ->linetype->toEqual(1)
            ->color->toEqual($expected[0])
            ->x1->toEqual($expected[1])
            ->y1->toEqual($expected[2])
            ->z1->toEqual($expected[3])
            ->a->toEqual($expected[4])
            ->b->toEqual($expected[5])
            ->c->toEqual($expected[6])
            ->d->toEqual($expected[7])
            ->e->toEqual($expected[8])
            ->f->toEqual($expected[9])
            ->g->toEqual($expected[10])
            ->h->toEqual($expected[11])
            ->i->toEqual($expected[12])
            ->file->toEqual($expected[13]);
    })->with([
        'whole numbers' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", [16, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 'test.dat']],
        'floats' => ["1 16 0.1 .2 3.0 -0.4 -.5 -6.0 -70 80 0 0 0 1 test.dat", [16, 0.1, 0.2, 3, -0.4, -0.5, -6, -70, 80, 0, 0, 0, 1, 'test.dat']],
        'hex color' => ["1 0x2121212 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", ['0x2121212', 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 'test.dat']],
        'ldr extension' => ["1 16 0 0 0 1 0 0 0 1 0 0 0 1 test.ldr", [16, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 1, 'test.ldr']],
    ]);

    it('fails type 1 parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype', 'invalid'])
            ->linetype->toBeString()->toBe('invalid')
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "1 16 a 0 0 1 0 0 0 1 0 0 0 1 test.dat",
        'too few numbers' => "1 16 0 0 1 0 0 0 1 0 0 0 1 test.dat",
    ]);

    it('subparts parse', function (string $input, ?array $expected) {
        $file = new ParsedPartCollection($input);
        $subparts = $file->subparts();
        expect($subparts)->toBe($expected);
    })->with([
        'single subpart' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", ['test.dat']],
        'no type 1 lines' => ["3 0 1 1 1 0 0 0 -1 -1 -1", null],
        'texmap planer' => ["0 !TEXMAP START PLANAR 1 2 3 1 2 3 1 2 3 test.png", ['test.png']],
        'texmap spherical with glossmap' => ["0 !TEXMAP START SPHERICAL 1 2 3 1 2 3 1 2 3 test.png GLOSSMAP test2.png", ['test.png', 'test2.png']],
        'texmap and type 1 lines' => [
            "1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat\n0 !TEXMAP START PLANAR 1 2 3 1 2 3 1 2 3 test.png GLOSSMAP test2.png",
            ['test.dat', 'test.png', 'test2.dat', 'test2.png']
        ],
        'same subparts' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat", ['test.dat']],
        'multiple subparts' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.dat\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", ['test.dat', 'test2.dat']],
        'ldr and dat file' => ["1 0 0 0 0 1 0 0 0 1 0 0 0 1 test.ldr\n1 0 0 0 0 1 0 0 0 1 0 0 0 1 test2.dat", ['test.ldr', 'test2.dat']],
    ]);
});

describe('type 2 parse', function () {
    it('passes type 2 parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype','color', 'x1', 'y1', 'z1', 'x2', 'y2', 'z2'])
            ->linetype->toEqual(2)
            ->color->toEqual($expected[0])
            ->x1->toEqual($expected[1])
            ->y1->toEqual($expected[2])
            ->z1->toEqual($expected[3])
            ->x2->toEqual($expected[4])
            ->y2->toEqual($expected[5])
            ->z2->toEqual($expected[6]);
    })->with([
        'whole numbers' => ["2 16 0 0 0 1 0 0", [16, 0, 0, 0, 1, 0, 0]],
        'floats' => ["2 16 0.1 .2 3.0 -0.4 -.5 -6.0", [16, 0.1, 0.2, 3, -0.4, -0.5, -6]],
        'hex color' => ["2 0x2121212 0 0 0 1 0 0", ['0x2121212', 0, 0, 0, 1, 0, 0]],
    ]);

    it('fails type 2 parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype', 'invalid'])
            ->linetype->toBeString()->toBe('invalid')
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "2 16 a 0 0 1 0 0",
        'too few numbers' => "2 16 0 0 1 0 0",
        'too many numbers' => "2 16 0 0 1 0 0 0 0",
    ]);
});

describe('type 3 parse', function () {
    it('passes type 3 parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype','color', 'x1', 'y1', 'z1', 'x2', 'y2', 'z2', 'x3', 'y3', 'z3'])
            ->linetype->toEqual(3)
            ->color->toEqual($expected[0])
            ->x1->toEqual($expected[1])
            ->y1->toEqual($expected[2])
            ->z1->toEqual($expected[3])
            ->x2->toEqual($expected[4])
            ->y2->toEqual($expected[5])
            ->z2->toEqual($expected[6])
            ->x3->toEqual($expected[7])
            ->y3->toEqual($expected[8])
            ->z3->toEqual($expected[9]);
    })->with([
        'whole numbers' => ["3 16 0 0 0 1 0 0 1 0 0", [16, 0, 0, 0, 1, 0, 0, 1, 0, 0]],
        'floats' => ["3 16 0.1 .2 3.0 -0.4 -.5 -6.0 1 0 0", [16, 0.1, 0.2, 3, -0.4, -0.5, -6, 1, 0, 0]],
        'hex color' => ["3 0x2121212 0 0 0 1 0 0 1 0 0", ['0x2121212', 0, 0, 0, 1, 0, 0, 1, 0, 0]],
    ]);

    it('fails type 3 parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype', 'invalid'])
            ->linetype->toBeString()->toBe('invalid')
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "3 16 a 0 0 1 0 0 1 0 0",
        'too few numbers' => "3 16 0 0 1 0 0 1 0 0",
        'too many numbers' => "3 16 0 0 1 0 0 0 0 1 0 0",
    ]);
});

describe('type 4 parse', function () {
    it('passes type 4 parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype','color', 'x1', 'y1', 'z1', 'x2', 'y2', 'z2', 'x3', 'y3', 'z3', 'x4', 'y4', 'z4'])
            ->linetype->toEqual(4)
            ->color->toEqual($expected[0])
            ->x1->toEqual($expected[1])
            ->y1->toEqual($expected[2])
            ->z1->toEqual($expected[3])
            ->x2->toEqual($expected[4])
            ->y2->toEqual($expected[5])
            ->z2->toEqual($expected[6])
            ->x3->toEqual($expected[7])
            ->y3->toEqual($expected[8])
            ->z3->toEqual($expected[9])
            ->x4->toEqual($expected[10])
            ->y4->toEqual($expected[11])
            ->z4->toEqual($expected[12]);
    })->with([
        'whole numbers' => ["4 16 0 0 0 1 0 0 1 0 0 1 0 0", [16, 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0]],
        'floats' => ["4 16 0.1 .2 3.0 -0.4 -.5 -6.0 1 0 0 1 0 0", [16, 0.1, 0.2, 3, -0.4, -0.5, -6, 1, 0, 0, 1, 0, 0]],
        'hex color' => ["4 0x2121212 0 0 0 1 0 0 1 0 0 1 0 0", ['0x2121212', 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0]],
    ]);

    it('fails type 4 parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype', 'invalid'])
            ->linetype->toBeString()->toBe('invalid')
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "4 16 a 0 0 1 0 0 1 0 0 1 0 0",
        'too few numbers' => "4 16 0 0 1 0 0 1 0 0 1 0 0",
        'too many numbers' => "4 16 0 0 1 0 0 0 0 1 0 0 1 0 0",
    ]);
});

describe('type 5 parse', function () {
    it('passes type 5 parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype','color', 'x1', 'y1', 'z1', 'x2', 'y2', 'z2', 'x3', 'y3', 'z3', 'x4', 'y4', 'z4'])
            ->linetype->toEqual(5)
            ->color->toEqual($expected[0])
            ->x1->toEqual($expected[1])
            ->y1->toEqual($expected[2])
            ->z1->toEqual($expected[3])
            ->x2->toEqual($expected[4])
            ->y2->toEqual($expected[5])
            ->z2->toEqual($expected[6])
            ->x3->toEqual($expected[7])
            ->y3->toEqual($expected[8])
            ->z3->toEqual($expected[9])
            ->x4->toEqual($expected[10])
            ->y4->toEqual($expected[11])
            ->z4->toEqual($expected[12]);
    })->with([
        'whole numbers' => ["5 16 0 0 0 1 0 0 1 0 0 1 0 0", [16, 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0]],
        'floats' => ["5 16 0.1 .2 3.0 -0.4 -.5 -6.0 1 0 0 1 0 0", [16, 0.1, 0.2, 3, -0.4, -0.5, -6, 1, 0, 0, 1, 0, 0]],
        'hex color' => ["5 0x2121212 0 0 0 1 0 0 1 0 0 1 0 0", ['0x2121212', 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0]],
    ]);

    it('fails type 5 parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['linetype', 'invalid'])
            ->linetype->toBeString()->toBe('invalid')
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "5 16 a 0 0 1 0 0 1 0 0 1 0 0",
        'too few numbers' => "5 16 0 0 1 0 0 1 0 0 1 0 0",
        'too many numbers' => "5 16 0 0 1 0 0 0 0 1 0 0 1 0 0",
    ]);
});

describe('parse avatar', function () {
    it('passes avatar parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['category', 'description', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'file'])
            ->category->toBeString()->toBe($expected[0])
            ->description->toBeString()->toBe($expected[1])
            ->a->toEqual($expected[2])
            ->b->toEqual($expected[3])
            ->c->toEqual($expected[4])
            ->d->toEqual($expected[5])
            ->e->toEqual($expected[6])
            ->f->toEqual($expected[7])
            ->g->toEqual($expected[8])
            ->h->toEqual($expected[9])
            ->i->toEqual($expected[10])
            ->file->toBeString()->toBe($expected[11]);
    })->with([
        'valid, single words' => ["0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1 0 0 0 1 0 0 0 1 \"test.dat\"", ['Category', 'Test', 1, 0, 0, 0, 1, 0, 0, 0, 1, 'test.dat']],
        'valid, muliple words' => ["0 !AVATAR CATEGORY \"Category Test\" DESCRIPTION \"Test Description\" PART 1 0 0 0 1 0 0 0 1 \"test.dat\"", ['Category Test', 'Test Description', 1, 0, 0, 0, 1, 0, 0, 0, 1, 'test.dat']],
    ]);

    it('fails avatar parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $type1line = $file->first();
        expect($type1line)
            ->toBeArray()
            ->toHaveKeys(['invalid'])
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'bad number' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1 a 0 0 1 0 0 0 1 \"test.dat\"",
        'too few numbers' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1  0 0 1 0 0 0 1 \"test.dat\"",
        'too many numbers' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1 0 0 0 0 1 0 0 0 1 \"test.dat\"",
        'no quotes, category' => "0 !AVATAR CATEGORY Category DESCRIPTION \"Test\" PART 1 0 0 0 1 0 0 0 1 \"test.dat\"",
        'no quotes, description' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION Test PART 1 0 0 0 1 0 0 0 1 \"test.dat\"",
        'no quotes, file' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1 0 0 0 1 0 0 0 1 test.dat",
        'blank category' => "0 !AVATAR CATEGORY \"\" DESCRIPTION \"Test\" PART 1 0 0 0 1 0 0 0 1 \"test.dat\"",
        'blank description' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"\" PART 1 0 0 0 1 0 0 0 1 \"test.dat\"",
        'blank file' => "0 !AVATAR CATEGORY \"Category\" DESCRIPTION \"Test\" PART 1 0 0 0 1 0 0 0 1 \"\"",
    ]);
});

describe('parse colour', function () {
    it('passes colour parse', function (string $input, array $expected) {
        $file = new ParsedPartCollection($input);
        $color = $file->first();
        expect($color)
            ->toBeArray()
            ->toHaveKeys(['name', 'code', 'value', 'edge', 'alpha', 'luminance', 'material', 'material_params'])
            ->name->toBeString()->toBe($expected['name'])
            ->code->toBeNumeric()->toEqual($expected['code'])
            ->value->toBeString()->toBe($expected['value'])
            ->edge->toBeString()->toBe($expected['edge']);
        if(Arr::has($expected, 'alpha')) {
            expect($color)
                ->alpha->toBeNumeric()->toEqual(Arr::get($expected, 'alpha'));          
        } else {
            expect($color)
                ->alpha->toBeNull();
        }
        if(Arr::has($expected, 'luminance')) {
            expect($color)
                ->luminance->toBeNumeric()->toEqual(Arr::get($expected, 'luminance'));          
        } else {
            expect($color)
                ->luminance->toBeNull();
        }
        if(Arr::has($expected, 'material')) {
            expect($color)
                ->material->toBeIn(['CHROME', 'PEARLESCENT', 'RUBBER', 'MATTE_METALLIC', 'METAL', 'MATERIAL'])
                ->toBe(Arr::get($expected, 'material'));          
        } else {
            expect($color)
                 ->material->toBeNull();
        }
        if(Arr::has($expected, 'material_params')) {
            expect($color)->material_params
                ->toBeArray()
                ->toHaveKeys(['material_type', 'value', 'alpha', 'luminance', 'fraction', 'vfraction', 'size', 'minsize', 'maxsize', 'fabric_type'])
                ->toMatchArray(Arr::get($expected, 'material_params'));          
        } else {
            expect($color)
                 ->material_params->toBeNull();
        }
    })->with([
        'normal colour' => [
             '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080', 
             [
                 'name' => 'Black',
                 'code' => 0,
                 'value' => '#1B2A34',
                 'edge' => '#808080',
             ]
        ],
        'transparent colour' => [
             '0 !COLOUR Trans_Dark_Blue CODE 33 VALUE #0020A0 EDGE #000B38 ALPHA 128', 
             [
                 'name' => 'Trans_Dark_Blue',
                 'code' => 33,
                 'value' => '#0020A0',
                 'edge' => '#000B38',
                 'alpha' => 128,
             ]
        ],
        'chrome colour' => [
             '0 !COLOUR Chrome_Antique_Brass CODE 60 VALUE #645A4C EDGE #665B4D CHROME', 
             [
                 'name' => 'Chrome_Antique_Brass',
                 'code' => 60,
                 'value' => '#645A4C',
                 'edge' => '#665B4D',
                 'material' => 'CHROME',
             ]
        ],
        'pearlescent colour' => [
             '0 !COLOUR Pearl_Black CODE 83 VALUE #0A1327 EDGE #333333 PEARLESCENT', 
             [
                 'name' => 'Pearl_Black',
                 'code' => 83,
                 'value' => '#0A1327',
                 'edge' => '#333333',
                 'material' => 'PEARLESCENT',
             ]
        ],
        'metal colour' => [
             '0 !COLOUR Metallic_Silver CODE 80 VALUE #767676 EDGE #333333 METAL', 
             [
                 'name' => 'Metallic_Silver',
                 'code' => 80,
                 'value' => '#767676',
                 'edge' => '#333333',
                 'material' => 'METAL',
             ]
        ],
        'rubber colour' => [
             '0 !COLOUR Rubber_Dark_Orange CODE 10484 VALUE #91501C EDGE #333333 RUBBER', 
             [
                 'name' => 'Rubber_Dark_Orange',
                 'code' => 10484,
                 'value' => '#91501C',
                 'edge' => '#333333',
                 'material' => 'RUBBER',
             ]
        ],
        'luminance colour' => [
             '0 !COLOUR Glow_In_Dark_Opaque CODE 21 VALUE #E0FFB0 EDGE #B8FF4D ALPHA 240 LUMINANCE 15', 
             [
                 'name' => 'Glow_In_Dark_Opaque',
                 'code' => 21,
                 'value' => '#E0FFB0',
                 'edge' => '#B8FF4D',
                 'alpha' => '240',
                 'luminance' => '15',
             ]
        ],
        'glitter colour' => [
             '0 !COLOUR Glitter_Trans_Dark_Pink CODE 114 VALUE #DF6695 EDGE #B9275F ALPHA 128 MATERIAL GLITTER VALUE #B92790 FRACTION 0.17 VFRACTION 0.2 SIZE 1', 
             [
                 'name' => 'Glitter_Trans_Dark_Pink',
                 'code' => 114,
                 'value' => '#DF6695',
                 'edge' => '#B9275F',
                 'alpha' => 128,
                 'material' => 'MATERIAL',
                 'material_params' => [
                     'material_type' => 'GLITTER',
                     'value' => '#B92790',
                     'fraction' => 0.17,
                     'vfraction' => 0.2,
                     'size' => 1,
                 ]
             ]
        ],
        'opal colour' => [
             '0 !COLOUR Opal_Trans_Clear CODE 360 VALUE #FCFCFC EDGE #C9C9C9 ALPHA 240 LUMINANCE 5 MATERIAL GLITTER VALUE #FFFFFF FRACTION 0.8 VFRACTION 0.6 MINSIZE 0.02 MAXSIZE 0.1', 
             [
                 'name' => 'Opal_Trans_Clear',
                 'code' => 360,
                 'value' => '#FCFCFC',
                 'edge' => '#C9C9C9',
                 'alpha' => 240,
                 'luminance' => 5,
                 'material' => 'MATERIAL',
                 'material_params' => [
                     'material_type' => 'GLITTER',
                     'value' => '#FFFFFF',
                     'fraction' => 0.8,
                     'vfraction' => 0.6,
                     'minsize' => 0.02,
                     'maxsize' => 0.1
                 ]
             ]
        ],
        'speckle colour' => [
             '0 !COLOUR Speckle_Black_Copper CODE 75 VALUE #000000 EDGE #AB6038 MATERIAL SPECKLE VALUE #AB6038 FRACTION 0.4 MINSIZE 1 MAXSIZE 3', 
             [
                 'name' => 'Speckle_Black_Copper',
                 'code' => 75,
                 'value' => '#000000',
                 'edge' => '#AB6038',
                 'material' => 'MATERIAL',
                 'material_params' => [
                     'material_type' => 'SPECKLE',
                     'value' => '#AB6038',
                     'fraction' => 0.4,
                     'minsize' => 1,
                     'maxsize' => 3
                 ]
             ]
        ],
        'fabric colour' => [
             '0 !COLOUR Canvas_Black CODE 20000 VALUE #1B2A34 EDGE #808080 MATERIAL FABRIC CANVAS', 
             [
                 'name' => 'Canvas_Black',
                 'code' => 20000,
                 'value' => '#1B2A34',
                 'edge' => '#808080',
                 'material' => 'MATERIAL',
                 'material_params' => [
                     'material_type' => 'FABRIC',
                     'fabric_type' => 'CANVAS'
                 ]
             ]
        ],
    ]);

    it('fails colour parse', function (string $input) {
        $file = new ParsedPartCollection($input);
        $color = $file->first();
        expect($color)
            ->toBeArray()
            ->toHaveKeys(['invalid'])
            ->invalid->toBeBool()->toBeTrue();
    })->with([
        'multi-word name no underscore' => '0 !COLOUR Black Black CODE 0 VALUE #1B2A34 EDGE #808080',
        'blank name' => '0 !COLOUR CODE 0 VALUE #1B2A34 EDGE #808080',
        'letter code number' => '0 !COLOUR Black CODE a VALUE #1B2A34 EDGE #808080',
        'float code number' => '0 !COLOUR Black CODE 1.0 VALUE #1B2A34 EDGE #808080',
        'blank code' => '0 !COLOUR Black CODE VALUE #1B2A34 EDGE #808080',
        'missing code' => '0 !COLOUR Black VALUE #1B2A34 EDGE #808080',
        'letter value' => '0 !COLOUR Black CODE 0 VALUE a EDGE #808080',
        'float value' => '0 !COLOUR Black CODE 0 VALUE 0.0 EDGE #808080',
        'missing rgb prefix value' => '0 !COLOUR Black CODE 0 VALUE AAAAAA EDGE #808080',
        'bad prefix value' => '0 !COLOUR Black CODE 0 VALUE ^AAAAAA EDGE #808080',
        'blank value' => '0 !COLOUR Black CODE 0 VALUE EDGE #808080',
        'missing value' => '0 !COLOUR Black CODE 0 EDGE #808080',
        'letter edge' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE a',
        'float edge' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE 0.0',
        'missing rgb prefix edge' => '0 !COLOUR Black CODE 0 VALUE #AAAAAA EDGE AAAAAA',
        'bad prefix edge' => '0 !COLOUR Black CODE 0 VALUE #AAAAAA EDGE &808080',
        'blank edge' => '0 !COLOUR Black CODE 0 VALUE #AAAAAA EDGE ',
        'missing edge' => '0 !COLOUR Black CODE 0 VALUE #AAAAAA ',
        'letter alpha' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 ALPHA a',
        'float alpha' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 ALPHA 1.0',
        'blank alpha' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 ALPHA ',
        'letter luminance' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 LUMINANCE a',
        'float luminance' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 LUMINANCE 1.0',
        'blank luminance' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 LUMINANCE ',
        'wrong material' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 CLOTH',
        'material with invalid type' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL PLASTIC',
        'material with missing type' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL VALUE #AAAAAA',
        'material with letter value param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE a',
        'material with float value param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE 1.0',
        'material with missing prefix value param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE AAAAAA',
        'material with incorrect prefix value param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE @AAAAAA',
        'material with blank value param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE',
        'material with letter alpha param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA ALPHA a',
        'material with float alpha param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA ALPHA 1.0',
        'material with blank alpha param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA ALPHA ',
        'material with letter luminance param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA LUMINANCE a',
        'material with float luminance param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA LUMINANCE 1.0',
        'material with blank luminance param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA LUMINANCE ',
        'material with letter fraction param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA FRACTION a',
        'material with blank fraction param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA FRACTION ',
        'material with letter vfraction param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA VFRACTION a',
        'material with blank vfraction param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA VFRACTION ',
        'material with letter size param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA SIZE a',
        'material with blank size param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA SIZE ',
        'material with letter maxsize param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA MAXSIZE a',
        'material with blank maxsize param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA MAXSIZE ',
        'material with letter minsize param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA MINSIZE a',
        'material with blank minsize param' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL GLITTER VALUE #AAAAAA MINSIZE ',
        'material with invalid fabric type' => '0 !COLOUR Black CODE 0 VALUE #1B2A34 EDGE #808080 MATERIAL FABRIC SUEDE',
    ]);
});

describe('all file parse', function () {
    beforeEach(function () {
        User::factory()->create([
            'realname' => 'Thomas Burger',
            'name' => 'grapeape',
        ]);
    });

    it('passes parsing file', function () {
        $text = file_get_contents(__DIR__ . "/testfiles/parsetest.dat");
        $part = (new ParsedPartCollection($text));
        expect($part->description())->toBe('Brick  1 x  2 x  5 with SW Han Solo Carbonite Pattern');
        expect($part->name())->toBe('2454aps5.dat');
        expect($part->authorUser())
            ->toBeInstanceOf(User::class)
            ->name->toBe('grapeape')
            ->realname->toBe('Thomas Burger');
        expect($part->type())->toBe(PartType::Part);
        expect($part->type_qualifier())->toBe(PartTypeQualifier::Alias);
        expect($part->license())->toBe(License::CC_BY_2);
        expect($part->help())->toBe(['This is help', 'This is more help']);
        expect($part->headerBfc())->toBe('CW');
        expect($part->category())->toBe(PartCategory::MinifigAccessory);
        expect($part->keywords())->toBe(['Bespin', 'Boba Fett', 'Cloud City', 'cold sleep', 'deep freeze', 'Set 7144']);
        expect($part->cmdline())->toBe('-c0');
        expect($part->history())->toBe([
            ['date' => '2003-08-01', 'realname' => null, 'username' => 'PTadmin', 'comment' => 'Official Update 2003-02'],
            ['date' => '2007-05-10', 'realname' => null, 'username' => 'PTadmin', 'comment' => 'Header formatted for Contributor Agreement'],
        ]);
        expect($part->subparts())->toBe(['2454aps5.png', 's\2454as01.dat']);
        expect($part->hasInvalidLines())->toBeBool()->toBeFalse();
        expect($part->bodyStartLine())->toBeInt()->toBe(22);
    });
});
