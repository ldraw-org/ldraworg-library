<?php

namespace App\Services\Parser;

use App\Enums\LDrawRegex;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ImprovedParser {

/*
     protected array $patterns = [
        'line_type_0' => '~^\h*(?<linetype>0)\h+(?P<content>(?P<first_word>\S+)(?:\h+(?P<rest>.*))?)$~u',
        'line_type_1' => '~^\h*(?<linetype>1)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+))\h*(?<file>.+?)\h*$~u',
        'line_type_2' => '~^\h*(?<linetype>2)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h*$~u',
        'line_type_3' => '~^\h*(?<linetype>3)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h*$~u',
        'line_type_4' => '~^\h*(?<linetype>4)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$~u',
        'line_type_5' => '~^\h*(?<linetype>5)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$~u',

        'description' => '#^\h*(?<linetype>0)\h+(?P<description>(?:(?P<prefix>[~_=|]+)\h*)?(?P<category>[^\h]+).*?)\h*$#u',

        'name' => '~^\h*(?<linetype>0)\h+Name:\h*(?P<name>[^\h]+)\h*$~u',
        'basepart' => '~^^(?<basepart>[uts]?\d+(?:[a-d][a-z]|[a-oq-z])?)~i',
        'suffix_validate' => '~(?<suffix>(?:(?:p[a-z0-9]{2,4}|c[0-9a-z]{2}|d[0-9a-z]{2}|k[0-9a-z]{2})+)?(?:-f[0-9a-z])?)$~i',
        'suffix_extract'  => '~p(?:\d{4}|[cd][0-9a-z][0-9a-l]|[0-9a-z]{2})|c[a-z0-9]{2}|d[a-z0-9]{2}|k[0-9a-z]{2}|-f[0-9a-z]~i',

        'author' => '~^\h*(?<linetype>0)\h+Author:\h*(?:(?P<realname>[^\[]*?)\h*)?(?:\[(?P<username>[A-Za-z0-9_.-]+)\])?\h*$~u',

        'ldraworg' => '~^\h*(?<linetype>0)\h+!LDRAW_ORG\h+(?:(?P<unofficial>Unofficial)_?)?(?P<type>###PartTypes###)(?:\h+(?P<type_qualifier>###PartTypeQualifiers###))?(?:\h+(?P<release_type>ORIGINAL|UPDATE))?(?:\h+(?P<release>\d{4}-\d{2}))?\h*$~u',
        'ld_config_ldraworg' => '~^\h*(?<linetype>0)\h+!LDRAW_ORG\h+Configuration\h+UPDATE\h+[0-9]{4}-[0-9]{2}-[0-9]{2}\h*~u',

        'category' => '~^\h*(?<linetype>0)\h+!CATEGORY\h+(?P<category>.*?)\h*$~u',
        'license' => '~^\h*(?<linetype>0)\h+!LICENSE\h+(?P<license>.*?)\h*$~u',
        'help' => '~^\h*(?<linetype>0)\h+!HELP\h+(?P<help>.*?)\h*$~u',
        'keywords' => '~^\h*(?<linetype>0)\h+!KEYWORDS\h+(?P<keywords>.*?)\h*$~u',
        'bfc' => '~^\h*(?<linetype>0)\h+BFC\h+(?<bfc>NOCERTIFY|CERTIFY|CW|CCW|CLIP|NOCLIP|INVERTNEXT)(?:\h+(?<winding>CW|CCW))?\h*$~iu',
        'cmdline' => '~^\h*(?<linetype>0)\h+!CMDLINE\h+(?P<cmdline>.*?)\h*$~u',
        'preview' => '~^\h*(?<linetype>0)\h+!PREVIEW\h+(?<color>\d+)\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<rotation_matrix>(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+)))\h*$~u',
        'history' =>  '~^\h*(?<linetype>0)\h+!HISTORY\h+(?P<date>\d{4}-\d{2}-\d{2})\h+(?:\[(?P<username>[a-zA-Z0-9_.-]+)\]|\{(?P<realname>[^\}]+)\})\h+(?P<comment>.+?)\h*$~u',

        'comment' => '~^\h*(?<linetype>0)\h+\/\/(?:\h+(?P<comment>.*))$~u',
        'texmap_geometry' => '~^\h*(?<linetype>0)\h+!\:\h*(?P<tex_line>.+?)\h*$~u',
        'texmap' => '~^\h*(?<linetype>0)\h+!TEXMAP\h+(?P<command>START|NEXT|FALLBACK|END)(?:\h+(?P<method>PLANAR|CYLINDRICAL|SPHERICAL)\h+(?P<params>(?:[-+]?[0-9]*\.?[0-9]+\h+){8,10}[-+]?[0-9]*\.?[0-9]+)\h+(?P<file>\S+\.png)(?:\h+GLOSSMAP\h+(?P<glossfile>\S+\.png))?)?\h*$~u',
        'colour' => '~^0\h+!COLOUR\h+(?P<name>[A-Za-z0-9_]+)\h+CODE\h+(?P<code>\d+)\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6})(?:\h+EDGE\h+(?P<edge>(?:\d+|(?:0x|#)[A-Fa-f0-9]{6})))(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+(?P<material>(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL)(?:\h+(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL))*))?(?:\h+MATERIAL\h+(?P<material_params>.*))?$~u',
        'colour_material' => '~^(?P<material_type>GLITTER|SPECKLE|FABRIC)(?:\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6}))?(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+FRACTION\h+(?P<fraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+VFRACTION\h+(?P<vfraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+SIZE\h+(?P<size>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+MINSIZE\h+(?P<minsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*))\h+MAXSIZE\h+(?P<maxsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+(?P<fabric_type>VELVET|CANVAS|STRING|FUR))?$~u',
        'avatar' => '~^\h*(?<linetype>0)\h+!AVATAR\h+CATEGORY\h+"(?P<category>[^"]+)"\h+DESCRIPTION\h+"(?P<description>[^"]+)"\h+PART\h+(?P<a>-?\d+(?:\.\d+)?)\h+(?P<b>-?\d+(?:\.\d+)?)\h+(?P<c>-?\d+(?:\.\d+)?)\h+(?P<d>-?\d+(?:\.\d+)?)\h+(?P<e>-?\d+(?:\.\d+)?)\h+(?P<f>-?\d+(?:\.\d+)?)\h+(?P<g>-?\d+(?:\.\d+)?)\h+(?P<h>-?\d+(?:\.\d+)?)\h+(?P<i>-?\d+(?:\.\d+)?)\h+"(?P<file>[^"]+)"$~u',
    ];

    public function __construct()
    {
        $this->patterns['ldraworg'] =
            str_replace(
                [
                    '###PartTypes###',
                    '###PartTypeQualifiers###'
                ],
                [
                    implode('|', array_column(PartType::cases(), 'value')),
                    implode('|', array_column(PartTypeQualifier::cases(), 'value'))
                ],
                $this->patterns['ldraworg']
            );
    }
*/
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
        $matches = [];

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

    function matchNameCommand(string $line): array
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
                $match['suffix_invalid'] = false;
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
