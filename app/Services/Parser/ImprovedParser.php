<?php

namespace App\Services\Parser;

use App\Enums\LDrawRegex;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ImprovedParser {

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

    public function matchPerLine(string $text): Collection {
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
                $m = match ($line[0]) {
                    '0' => LDrawRegex::LineType0->match($line, $match),
                    '1' => LDrawRegex::LineType1->match($line, $match),
                    '2' => LDrawRegex::LineType2->match($line, $match),
                    '3' => LDrawRegex::LineType3->match($line, $match),
                    '4' => LDrawRegex::LineType4->match($line, $match),
                    '5' => LDrawRegex::LineType5->match($line, $match),
                    default => false
                };
                if ($m) {
                    $match['invalid'] = false;
                    if ($line[0] === '0') {
                        switch($match['first_word']) {
                            case 'Name:':
                                $match = $this->matchNameCommand($line);
                                break;
                            case 'Author:':
                                $match = $this->matchMetaCommand(LDrawRegex::Author, $line);
                                break;
                            case '!LDRAW_ORG':
                                $match = $this->matchLDrawOrgCommand($line);
                                break;
                            case '!LICENSE':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::License, $line);
                                break;
                            case '!HELP':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::Help, $line);
                                break;
                            case 'BFC':
                                $match = $this->matchBfcCommand($line);
                                break;
                            case '!CATEGORY':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::Category, $line);
                                break;
                            case '!KEYWORDS':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::Keywords, $line);
                                break;
                            case '!CMDLINE':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::Cmdline, $line);
                                break;
                            case '!PREVIEW':
                                $match = $this->matchMetaCommand(LDrawRegex::Preview, $line);
                                break;
                            case '!HISTORY':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::History, $line);
                                break;
                            case '!TEXMAP':
                                $match = $this->matchMetaCommand(LDrawRegex::Texmap, $line);
                                break;
                            case '//':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand(LDrawRegex::Comment, $line);
                                break;
                            case '!:':
                                $match = $this->matchTextureGeometryCommand($line);
                                break;
                            case '!COLOUR':
                                $match = $this->matchColourCommand($line);
                                break;
                            case '!AVATAR':
                                $match = $this->matchMetaCommand(LDrawRegex::Avatar, $line);
                                break;
                            default:
                                if ($lineNumber === 1) {
                                    $match = $this->matchMetaCommand(LDrawRegex::Description, $line);
                                } else {
                                    $match['meta'] = null;
                                    $match['invalid'] = true;
                                }
                        }
                    }
                    $data = $match;
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
        $command = Arr::get($match, 'bfc');
        $winding = Arr::get($match, 'winding');

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
        $m = match ($match['tex_line'][0]) {
            '1' => LDrawRegex::LineType1->match($match['tex_line'], $lmatch),
            '2' => LDrawRegex::LineType2->match($match['tex_line'], $lmatch),
            '3' => LDrawRegex::LineType3->match($match['tex_line'], $lmatch),
            '4' => LDrawRegex::LineType4->match($match['tex_line'], $lmatch),
            '5' => LDrawRegex::LineType5->match($match['tex_line'], $lmatch),
            default => false
        };
        if (!$m) {
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

        $filename = basename(str_replace('\\', '/', Arr::get($match, 'name')),'.dat');
        $s = [];
        LDrawRegex::Basepart->match($filename, $bp);
        LDrawRegex::SuffixValidate->match($filename, $s);
        $suffixes = Arr::get($s, 'suffix', '');
        $basepart = str_replace($suffixes, '', $filename);
        if ($basepart . $suffixes != $filename) {
            $match['basepart'] = null;
            $match['suffixes'] = null;
            $match['suffixes_invalid'] = true;
            return $match;
        } if ($suffixes != '') {
            preg_match_all(LDrawRegex::SuffixExtract->value, $suffixes, $matches);
            $match['basepart'] = $basepart;
            if(implode('', Arr::get($matches, 0, [])) == $suffixes) {
                $match['suffixes'] = Arr::get($matches, 0);
                $match['suffixes_invalid'] = false;
            } else {
                $match['suffixes'] = $suffixes;
                $match['suffixes_invalid'] = true;
            }
        } else {
            $match['basepart'] = $basepart;
            $match['suffixes'] = null;
            $match['suffix_invalid'] = false;
        }
        return $match;
    }
}
