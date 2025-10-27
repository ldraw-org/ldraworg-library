<?php

namespace App\Services\Parser;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ImprovedParser {
    protected array $patterns = [
        'line_type_0' => '~^\h*(?<linetype>0)\h+(?P<content>(?P<first_word>\S+)(?:\h+(?P<rest>.*))?)$~u',
        'line_type_1' => '#^\h*(?<linetype>1)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+))\h*(?<file>.+?)\h*$#u',
        'line_type_2' => '#^\h*(?<linetype>2)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h*$#u',
        'line_type_3' => '#^\h*(?<linetype>3)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h*$#u',
        'line_type_4' => '#^\h*(?<linetype>4)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$#u',
        'line_type_5' => '#^\h*(?<linetype>5)\h+(?<color>(?:\d+|0x2[0-9A-Fa-f]{6}))\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<x2>-?(?:\d*\.\d+|\d+))\h+(?<y2>-?(?:\d*\.\d+|\d+))\h+(?<z2>-?(?:\d*\.\d+|\d+))\h+(?<x3>-?(?:\d*\.\d+|\d+))\h+(?<y3>-?(?:\d*\.\d+|\d+))\h+(?<z3>-?(?:\d*\.\d+|\d+))\h+(?<x4>-?(?:\d*\.\d+|\d+))\h+(?<y4>-?(?:\d*\.\d+|\d+))\h+(?<z4>-?(?:\d*\.\d+|\d+))\h*$#u',

        'description' => '#^\h*(?<linetype>0)\h+(?P<description>(?:(?P<prefix>[~_=|]+)\h*)?(?P<category>[^\h]+).*?)\h*$#u',

        'name' => '#^\h*(?<linetype>0)\h+Name:\h*(?P<name>[^\h]+)\h*$#u',
        'basepart' => '~^^(?<basepart>[uts]?\d+(?:[a-d][a-z]|[a-oq-z])?)~i',
//        'suffix_validate' => '~(?<suffix>(?:(?:p(?:[0-9a-z]{2}|\d{4}|[cd][0-9a-z][0-9a-l])|c[0-9a-z]{2}|d[0-9a-z]{2}|k\d{2})+)?(?:-f\d+)?)$~i',
        'suffix_validate' => '~(?<suffix>(?:(?:p[a-z0-9]{2,4}|c[0-9a-z]{2}|d[0-9a-z]{2}|k[0-9a-z]{2})+)?(?:-f[0-9a-z])?)$~i',
        'suffix_extract'  => '~p(?:\d{4}|[cd][0-9a-z][0-9a-l]|[0-9a-z]{2})|c[a-z0-9]{2}|d[a-z0-9]{2}|k[0-9a-z]{2}|-f[0-9a-z]~i',

        'author' => '#^\h*(?<linetype>0)\h+Author:\h*(?:(?P<realname>[^\[]*?)\h*)?(?:\[(?P<username>[A-Za-z0-9_.-]+)\])?\h*$#u',
        'ldraworg' => '#^\h*(?<linetype>0)\h+!LDRAW_ORG\h+(?:(?P<unofficial>Unofficial)_?)?(?P<type>###PartTypes###)(?:\h+(?P<type_qualifier>###PartTypeQualifiers###))?(?:\h+(?P<release_type>ORIGINAL|UPDATE))?(?:\h+(?P<release>\d{4}-\d{2}))?\h*$#u',
        'category' => '#^\h*(?<linetype>0)\h+!CATEGORY\h+(?P<category>.*?)\h*$#u',
        'license' => '#^\h*(?<linetype>0)\h+!LICENSE\h+(?P<license>.*?)\h*$#u',
        'help' => '#^\h*(?<linetype>0)\h+!HELP\h+(?P<help>.*?)\h*$#u',
        'keywords' => '#^\h*(?<linetype>0)\h+!KEYWORDS\h+(?P<keywords>.*?)\h*$#u',
        'bfc' => '~^\h*(?<linetype>0)\h+BFC\h+(?<bfc>NOCERTIFY|CERTIFY|CW|CCW|CLIP|NOCLIP|INVERTNEXT)(?:\h+(?<winding>CW|CCW))?\h*$~iu',
        'cmdline' => '#^\h*(?<linetype>0)\h+!CMDLINE\h+(?P<cmdline>.*?)\h*$#u',
        'preview' => '#^\h*(?<linetype>0)\h+!PREVIEW\h+(?<color>\d+)\h+(?<x1>-?(?:\d*\.\d+|\d+))\h+(?<y1>-?(?:\d*\.\d+|\d+))\h+(?<z1>-?(?:\d*\.\d+|\d+))\h+(?<a>-?(?:\d*\.\d+|\d+))\h+(?<b>-?(?:\d*\.\d+|\d+))\h+(?<c>-?(?:\d*\.\d+|\d+))\h+(?<d>-?(?:\d*\.\d+|\d+))\h+(?<e>-?(?:\d*\.\d+|\d+))\h+(?<f>-?(?:\d*\.\d+|\d+))\h+(?<g>-?(?:\d*\.\d+|\d+))\h+(?<h>-?(?:\d*\.\d+|\d+))\h+(?<i>-?(?:\d*\.\d+|\d+))\h*$#u',
        'history' =>  '#^\h*(?<linetype>0)\h+!HISTORY\h+(?P<date>\d{4}-\d{2}-\d{2})\h+(?:\[(?P<username>[a-zA-Z0-9_.-]+)\]|\{(?P<realname>[^\}]+)\})\h+(?P<comment>.+?)\h*$#u',

        'comment' => '~^0\h+\/\/(?:\h+(?P<comment>.*))?$~u',
        'texmap_geometry' => '#^\h*(?<linetype>0)\h+!\:\h*(?P<tex_line>.+?)\h*$#u',
        'texmap' => '#^\h*(?<linetype>0)\h+!TEXMAP\h+(?P<command>START|NEXT|FALLBACK|END)(?:\h+(?P<method>PLANAR|CYLINDRICAL|SPHERICAL)\h+(?P<params>(?:[-+]?[0-9]*\.?[0-9]+\h+){8,10}[-+]?[0-9]*\.?[0-9]+)\h+(?P<file>\S+\.png)(?:\h+GLOSSMAP\h+(?P<glossfile>\S+\.png))?)?\h*$#u',
        'colour' => '~^0\h+!COLOUR\h+(?P<name>[A-Za-z0-9_]+)\h+CODE\h+(?P<code>\d+)\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6})(?:\h+EDGE\h+(?P<edge>(?:\d+|(?:0x|#)[A-Fa-f0-9]{6})))?(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+(?P<flags>(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL)(?:\h+(?:CHROME|PEARLESCENT|RUBBER|MATTE_METALLIC|METAL|MATERIAL))*))?(?:\h+MATERIAL\h+(?P<material_params>.*))?$~u',
        'colour_material' => '~^(?P<material_type>GLITTER|SPECKLE|FABRIC)(?:\h+VALUE\h+(?P<value>(?:0x|#)[A-Fa-f0-9]{6}))?(?:\h+ALPHA\h+(?P<alpha>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+LUMINANCE\h+(?P<luminance>(?:25[0-5]|2[0-4]\d|1?\d{1,2})))?(?:\h+FRACTION\h+(?P<fraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+VFRACTION\h+(?P<vfraction>0(?:\.\d+)?|1(?:\.0+)?))?(?:\h+SIZE\h+(?P<size>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+MINSIZE\h+(?P<minsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*))\h+MAXSIZE\h+(?P<maxsize>(?:[1-9]\d*(?:\.\d+)?|0?\.\d*[1-9]\d*)))?(?:\h+(?P<fabric_type>VELVET|CANVAS|STRING|FUR))?$~u',
        'avatar' => '#^\h*(?<linetype>0)\h+!AVATAR\h+CATEGORY\h+"(?P<category>[^"]+)"\h+DESCRIPTION\h+"(?P<description>[^"]+)"\h+PART\h+(?P<a>-?\d+(?:\.\d+)?)\h+(?P<b>-?\d+(?:\.\d+)?)\h+(?P<c>-?\d+(?:\.\d+)?)\h+(?P<d>-?\d+(?:\.\d+)?)\h+(?P<e>-?\d+(?:\.\d+)?)\h+(?P<f>-?\d+(?:\.\d+)?)\h+(?P<g>-?\d+(?:\.\d+)?)\h+(?P<h>-?\d+(?:\.\d+)?)\h+(?P<i>-?\d+(?:\.\d+)?)\h+"(?P<file>[^"]+)"$#u',
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
        $text = preg_replace('#\n{3,}#us', "\n\n", $text);
        if ($text == '') {
            return collect([]);
        }
        $file = $this->matchPerLine($text);
        return $file;
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
                    '0' => preg_match($this->patterns["line_type_0"], $line, $match, PREG_UNMATCHED_AS_NULL),
                    '1' => preg_match($this->patterns["line_type_1"], $line, $match, PREG_UNMATCHED_AS_NULL),
                    '2' => preg_match($this->patterns["line_type_2"], $line, $match, PREG_UNMATCHED_AS_NULL),
                    '3' => preg_match($this->patterns["line_type_3"], $line, $match, PREG_UNMATCHED_AS_NULL),
                    '4' => preg_match($this->patterns["line_type_4"], $line, $match, PREG_UNMATCHED_AS_NULL),
                    '5' => preg_match($this->patterns["line_type_5"], $line, $match, PREG_UNMATCHED_AS_NULL),
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
                                $match = $this->matchMetaCommand('author', $line);
                                break;
                            case '!LDRAW_ORG':
                                $match = $this->matchMetaCommand('ldraworg', $line);
                                break;
                            case '!LICENSE':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('license', $line);
                                break;
                            case '!HELP':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('help', $line);
                                break;
                            case 'BFC':
                                $match = $this->matchBfcCommand($line);
                                break;
                            case '!CATEGORY':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('category', $line);
                                break;
                            case '!KEYWORDS':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('keywords', $line);
                                break;
                            case '!CMDLINE':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('cmdline', $line);
                                break;
                            case '!PREVIEW':
                                $match = $this->matchMetaCommand('preview', $line);
                                break;
                            case '!HISTORY':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('history', $line);
                                break;
                            case '!TEXMAP':
                                $match = $this->matchMetaCommand('texmap', $line);
                                break;
                            case '//':
                                $line = preg_replace('#\h+#u', ' ', $line);
                                $match = $this->matchMetaCommand('comment', $line);
                                break;
                            case '!:':
                                $match = $this->matchTextureGeometryCommand($line);
                                break;
                            case '!COLOUR':
                                $match = $this->matchColourCommand($line);
                                break;
                            case '!AVATAR':
                                $match = $this->matchMetaCommand('avatar', $line);
                                break;
                            default:
                                if ($lineNumber === 1) {
                                    $match = $this->matchMetaCommand('description', $line);
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

    public function matchMetaCommand(string $type, string $line): array 
    {
        if ($type !== 'description') {
            $line = preg_replace('#\h+#u', ' ', $line);
        }
        if (! preg_match($this->patterns[$type], $line, $match, PREG_UNMATCHED_AS_NULL)) {
            $match['linetype'] = 0;
            $match['invalid'] = true;
        } else {
            $match['invalid'] = false;
        }
        $match['meta'] = $type;
        
        return $match;
    }

    public function matchBfcCommand(string $line): array
    {
        $match = $this->matchMetaCommand('bfc', $line);
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
        $match = $this->matchMetaCommand('texmap_geometry', $line);
        if ($match['invalid'] === true || trim($match['tex_line']) === '' || trim($match['tex_line'][0]) === '0') {
            $match['invalid'] = true;
            return $match;
        }
        $m = match ($match['tex_line'][0]) {
            '1' => preg_match($this->patterns["line_type_1"], $match['tex_line'], $lmatch, PREG_UNMATCHED_AS_NULL),
            '2' => preg_match($this->patterns["line_type_2"], $match['tex_line'], $lmatch, PREG_UNMATCHED_AS_NULL),
            '3' => preg_match($this->patterns["line_type_3"], $match['tex_line'], $lmatch, PREG_UNMATCHED_AS_NULL),
            '4' => preg_match($this->patterns["line_type_4"], $match['tex_line'], $lmatch, PREG_UNMATCHED_AS_NULL),
            '5' => preg_match($this->patterns["line_type_5"], $match['tex_line'], $lmatch, PREG_UNMATCHED_AS_NULL),
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
        $match = $this->matchMetaCommand('colour', $line);
        if ($match['invalid'] === true) {
            return $match;
        }
        if (!is_null(Arr::get($match, 'material_params'))) {
            $match['flags'] = 'MATERIAL';
            if (! preg_match($this->patterns['colour_material'], $match['material_params'], $material, PREG_UNMATCHED_AS_NULL)) {
                dd($match);
                $match['invalid'] = true;
                return $match;
            }
            
            $match['material_params'] = array_filter($material, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return $match;
    }

    function matchNameCommand(string $line): array 
    {
        $match = $this->matchMetaCommand('name', $line);
        if ($match['invalid'] === true) {
            return $match;
        }

        $filename = basename(str_replace('\\', '/', Arr::get($match, 'name')),'.dat');
        preg_match($this->patterns['basepart'], $filename, $bp);
        preg_match($this->patterns['suffix_validate'], $filename, $s);
        $suffixes = Arr::get($s, 'suffix', '');
        $prelim_basepart = Arr::get($bp, 'basepart', '');
        if ($prelim_basepart . $suffixes != $filename) {
            $basepart = substr($prelim_basepart, 0, -1);
        } else {
            $basepart = $prelim_basepart;
        }
        
        if ($basepart . $suffixes != $filename) {
            $match['basepart'] = null;
            $match['suffixes'] = null;
            $match['suffixes_invalid'] = true;
            return $match;
        } if ($suffixes != '') {
            preg_match_all($this->patterns['suffix_extract'], $suffixes, $matches);
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