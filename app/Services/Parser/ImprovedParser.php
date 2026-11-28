<?php

namespace App\Services\Parser;

use App\Enums\LDrawRegex;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ImprovedParser
{
    /**
     * Meta commands, keyed by first word, that need no handling beyond their regex.
     *
     * @var array<string, LDrawRegex>
     */
    protected const META_COMMANDS = [
        'Author:' => LDrawRegex::Author,
        '!LICENSE' => LDrawRegex::License,
        '!HELP' => LDrawRegex::Help,
        '!CATEGORY' => LDrawRegex::Category,
        '!KEYWORDS' => LDrawRegex::Keywords,
        '!CMDLINE' => LDrawRegex::Cmdline,
        '!PREVIEW' => LDrawRegex::Preview,
        '!HISTORY' => LDrawRegex::History,
        '!TEXMAP' => LDrawRegex::Texmap,
        '//' => LDrawRegex::Comment,
        '!AVATAR' => LDrawRegex::Avatar,
    ];

    public static function fixEncoding(string $text): string
    {
        $old_encode = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'ASCII']);
        $new_text = mb_convert_encoding($text, 'UTF-8', $old_encode);
        return $new_text;
    }

    public static function dosLineEndings(string $text): string
    {
        return preg_replace('#\R#us', "\r\n", $text);
    }

    public static function unixLineEndings(string $text): string
    {
        return preg_replace('#\R#us', "\n", $text);
    }

    public function parse(string $text): Collection
    {
        $text = self::fixEncoding($text);
        $text = trim($text);
        $text = self::unixLineEndings($text);
        // Squish spaces
        $text = preg_replace('~^0(?:\h+//)?\h*$~m', '', $text);
        // Squish new lines
        $text = preg_replace('#\n{3,}#us', "\n\n", $text);
        if ($text == '') {
            return collect([]);
        }

        return $this->matchPerLine($text);
    }

    public function matchPerLine(string $text): Collection
    {
        $lineNumber = 0;

        foreach (explode("\n", $text) as $line) {
            $lineNumber++;
            $line = trim($line);
            $match = [];
            if ($line === '') {
                $data = [
                    0 => '',
                    'linetype' => 'blank',
                    'invalid' => false,
                ];
            } else {
                if ($this->lineTypeRegex($line[0])?->match($line, $match)) {
                    $match['invalid'] = false;
                    $data = $line[0] === '0' ? $this->matchLineType0($match, $line, $lineNumber) : $match;
                } else {
                    $data = [
                        'linetype' => 'invalid',
                        'invalid' => true,
                    ];
                }
            }
            $data['line_number'] = $lineNumber;
            $data['text'] = Arr::get($data, 'description') ? $data[0] : preg_replace('#\h+#u', ' ', $line);
            $data = array_filter($data, 'is_string', ARRAY_FILTER_USE_KEY);
            $matches[] = $data;
        }
        return collect($matches);
    }

    protected function lineTypeRegex(string $lineType): ?LDrawRegex
    {
        return match ($lineType) {
            '0' => LDrawRegex::LineType0,
            '1' => LDrawRegex::LineType1,
            '2' => LDrawRegex::LineType2,
            '3' => LDrawRegex::LineType3,
            '4' => LDrawRegex::LineType4,
            '5' => LDrawRegex::LineType5,
            default => null,
        };
    }

    /**
     * Match a type 0 line against the meta command named by its first word.
     *
     * @param array<string, string|bool|null> $match The LineType0 match for the line
     */
    protected function matchLineType0(array $match, string $line, int $lineNumber): array
    {
        $firstWord = strtoupper($match['first_word']) === 'BFC' ? 'BFC' : $match['first_word'];

        if (array_key_exists($firstWord, self::META_COMMANDS)) {
            return $this->matchMetaCommand(self::META_COMMANDS[$firstWord], $line);
        }

        return match ($firstWord) {
            'Name:' => $this->matchNameCommand($line),
            '!LDRAW_ORG' => $this->matchLDrawOrgCommand($line),
            'BFC' => $this->matchBfcCommand($line),
            '!:' => $this->matchTextureGeometryCommand($line),
            '!COLOUR' => $this->matchColourCommand($line),
            default => $lineNumber === 1
                ? $this->matchMetaCommand(LDrawRegex::Description, $line)
                : [...$match, 'meta' => null, 'invalid' => true],
        };
    }

    public function matchMetaCommand(LDrawRegex $regex, string $line): array
    {
        if ($regex !== LDrawRegex::Description) {
            $line = preg_replace('#\h+#u', ' ', $line);
        }
        if (! $regex->match($line, $match)) {
            $match['linetype'] = 0;
            $match['invalid'] = true;
        } else {
            $match['invalid'] = false;
        }
        $match['meta'] = $regex->type();

        return $match;
    }

    public function matchLDrawOrgCommand(string $line): array
    {
        $match = $this->matchMetaCommand(LDrawRegex::Ldraworg, $line);
        if ($match['invalid'] === true) {
            $match = $this->matchMetaCommand(LDrawRegex::LdconfigLdraworg, $line);
        }
        return $match;
    }

    public function matchBfcCommand(string $line): array
    {
        $match = $this->matchMetaCommand(LDrawRegex::Bfc, $line);
        if ($match['invalid'] === true) {
            return $match;
        }
        $command = strtoupper($match['bfc']);
        $winding = is_null(Arr::get($match, 'winding')) ? null : strtoupper($match['winding']);
        $match['bfc'] = $command;
        $match['winding'] = $winding;

        if (!in_array($command, ['CERTIFY', 'CLIP']) && !is_null($winding)) {
            $match['invalid'] = true;
        }
        return $match;
    }

    public function matchTextureGeometryCommand(string $line): array
    {
        $match = $this->matchMetaCommand(LDrawRegex::TexmapGeometry, $line);
        if ($match['invalid'] === true || trim($match['tex_line']) === '' || trim($match['tex_line'][0]) === '0') {
            $match['invalid'] = true;
            return $match;
        }
        $lmatch = [];
        if (! $this->lineTypeRegex($match['tex_line'][0])?->match($match['tex_line'], $lmatch)) {
            $match['invalid'] = true;
            return $match;
        }
        $match['line'] = array_filter($lmatch, 'is_string', ARRAY_FILTER_USE_KEY);
        return $match;
    }

    public function matchColourCommand(string $line): array
    {
        $match = $this->matchMetaCommand(LDrawRegex::Colour, $line);
        if ($match['invalid'] === true) {
            return $match;
        }
        if (!is_null(Arr::get($match, 'material_params'))) {
            $match['material'] = 'MATERIAL';
            $material = [];
            if (! LDrawRegex::ColourMaterial->match($match['material_params'], $material)) {
                $match['invalid'] = true;
                return $match;
            }

            $match['material_params'] = array_filter($material, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return $match;
    }

    public function matchNameCommand(string $line): array
    {
        $match = $this->matchMetaCommand(LDrawRegex::Name, $line);
        if ($match['invalid'] === true) {
            return $match;
        }

        $filename = basename(str_replace('\\', '/', Arr::get($match, 'name')), '.dat');
        $s = [];
        LDrawRegex::SuffixValidate->match($filename, $s);
        $suffixes = Arr::get($s, 'suffix') ?? '';
        $basepart = substr($filename, 0, strlen($filename) - strlen($suffixes));
        if (! LDrawRegex::Basepart->hasMatches($basepart)) {
            $match['basepart'] = null;
            $match['suffixes'] = null;
            $match['suffixes_invalid'] = true;
            return $match;
        }
        if ($suffixes != '') {
            preg_match_all(LDrawRegex::SuffixExtract->value, $suffixes, $matches);
            $match['basepart'] = $basepart;
            if (implode('', Arr::get($matches, 0, [])) == $suffixes) {
                $match['suffixes'] = Arr::get($matches, 0);
                $match['suffixes_invalid'] = false;
            } else {
                $match['suffixes'] = $suffixes;
                $match['suffixes_invalid'] = true;
            }
        } else {
            $match['basepart'] = $basepart;
            $match['suffixes'] = null;
            $match['suffixes_invalid'] = false;
        }
        return $match;
    }
}
