<?php

namespace App\LDraw\Parse;

use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Settings\LibrarySettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Parser
{
    public function parse(string $part): ParsedPart
    {
        $text = $this->formatText($part);
        $author = $this->getAuthor($text);
        $type = $this->getType($text);
        $bfc = $this->getBFC($text);
        return new ParsedPart(
            $this->getDescription($text),
            mb_strtolower($this->getName($text)),
            $author['user'] ?? null,
            $author['realname'] ?? null,
            $type['unofficial'] ?? null,
            PartType::tryFrom(Arr::get($type, 'type') ?? ''),
            PartTypeQualifier::tryFrom(Arr::get($type, 'qual') ?? ''),
            $type['releasetype'] ?? null,
            $type['release'] ?? null,
            License::tryFromText($this->getLicense($text) ?? ''),
            $this->getHelp($text),
            !is_null($bfc) && $bfc['bfc'] == 'CERTIFY' ? $bfc['winding'] : null,
            $this->getMetaCategory($text),
            $this->getDescriptionCategory($text),
            $this->getKeywords($text),
            $this->getCmdLine($text),
            $this->getPreview($text),
            $this->getHistory($text),
            $this->getSubparts($text),
            $this->getBody($text),
            $part,
            $this->getBodyStart($text)
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

    protected function formatText(string $text): string
    {
        $text = self::fixEncoding($text);
        $text = trim($text);
        $text = self::unixLineEndings($text);
        $text = preg_replace('#\n{3,}#us', "\n\n", $text);
        $lines = explode("\n", $text);
        $first_line = array_shift($lines);
        $lines = Arr::map($lines, function (string $line, int $key) {
            $line = preg_replace('#\h+#u', ' ', trim($line));
            if (Str::startsWith($line, '1')) {
                $line = mb_strtolower($line);
            }
            return $line;
        });
        array_unshift($lines, $first_line);
        return implode("\n", $lines);
    }

    protected function patternMatch(string $pattern, string $text): ?array
    {
        $text = $this->formatText($text);
        if (!is_null(config("ldraw.patterns.{$pattern}")) && preg_match(config("ldraw.patterns.{$pattern}"), $text, $matches)) {
            return $matches;
        }
        return null;
    }

    protected function patternMatchAll(string $pattern, string $text, int $flags = 0): ?array
    {
        $text = $this->formatText($text);
        if (!is_null(config("ldraw.patterns.{$pattern}")) && preg_match_all(config("ldraw.patterns.{$pattern}"), $text, $matches, $flags)) {
            return $matches;
        }
        return null;
    }

    protected function getSingleValueMeta(string $text, string $meta): ?string
    {
        $matches = $this->patternMatch($meta, $text);
        if (!is_null($matches)) {
            $meta = trim($matches[$meta]);
            if ($meta !== '') {
                return $meta;
            }
        }
        return null;
    }

    public function getDescription(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'description');
    }

    public function getName(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'name');
    }

    public function getLicense(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'license');
    }

    public function getCmdLine(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'cmdline');
    }

    public function getPreview(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'preview');
    }

    public function getMetaCategory(string $text): ?PartCategory
    {
        $cat = $this->getSingleValueMeta($text, 'category');
        return PartCategory::tryFrom($cat);
    }

    public function getDescriptionCategory(string $text): ?PartCategory
    {
        $d = $this->getSingleValueMeta($text, 'description');
        if (!is_null($d)) {
            $word = 1;
            if (Str::of($d)->trim()->words(1, '')->replace(['~', '|', '=', '_'], '') == '') {
                $word = 2;
            }
            $cat = Str::of($d)->trim()->words($word, '')->replace(['~', '|', '=', '_', ' '], '')->toString();
            return PartCategory::tryFrom($cat);
        }
        return null;
    }

    public function getAuthor(string $text): ?array
    {
        $author = $this->patternMatch('author', $text);
        if (!is_null($author)) {
            $a = ['realname' => '', 'user' => ''];
            if (array_key_exists('user', $author)) {
                $a['user'] = $author['user'];
            }
            if (array_key_exists('realname', $author)) {
                $a['realname'] = $author['realname'];
            }

            return $a['realname'] !== '' || $a['user'] !== '' ? $a : null;
        }
        return null;
    }

    public function getKeywords(string $text): ?array
    {
        $kw = $this->patternMatchAll('keywords', $text);
        if (!is_null($kw)) {
            $keywords = [];
            foreach ($kw['keywords'] as $line) {
                foreach (explode(',', $line) as $word) {
                    $word = preg_replace('#^[\'"](.*)[\'"]$#u', '$1', trim($word));
                    if ($word !== '') {
                        $keywords[] = $word;
                    }
                }
            }
            $keywords = array_unique($keywords);
            if (count($keywords) > 0) {
                return $keywords;
            }
        }
        return null;
    }

    public function getType(string $text): ?array
    {
        $text = $this->formatText($text);
        if (config('ldraw.patterns.type')) {
            $pattern = str_replace(['###PartTypes###', '###PartTypesQualifiers###'], [implode('|', array_column(PartType::cases(), 'value')), implode('|', array_column(PartTypeQualifier::cases(), 'value'))], config('ldraw.patterns.type'));

            if (preg_match($pattern, $text, $matches)) {
                return [
                    'unofficial' => Arr::get($matches, 'unofficial') !== '',
                    'type' => Arr::get($matches, 'type'),
                    'qual' => Arr::get($matches, 'qual'),
                    'releasetype' => Arr::get($matches, 'releasetype'),
                    'release' => Arr::get($matches, 'releasetype') == 'ORIGINAL' ? 'original' : Arr::get($matches, 'release'),
                ];
            }
        }
        return null;
    }

    public function getHelp(string $text): ?array
    {
        $help = $this->patternMatchAll('help', $text);
        if (!is_null($help)) {
            $help = array_values(array_filter($help['help']));
            if (count($help) > 0) {
                return $help;
            }
        }
        return null;
    }

    public function getBFC(string $text): ?array
    {
        $bfc = $this->patternMatch('bfc', $text);
        if (!is_null($bfc)) {
            //preg_match optional pattern bug workaround
            $b = ['bfc' => $bfc['bfc'], 'winding' => ''];
            if (array_key_exists('winding', $bfc)) {
                $b['winding'] = $bfc['winding'];
            }
            return $b;
        }
        return null;
    }

    public function getHistory(string $text): ?array
    {
        $history = $this->patternMatchAll('history', $text, PREG_SET_ORDER);
        if (!is_null($history)) {
            foreach ($history as &$hist) {
                $hist = array_filter($hist, 'is_string', ARRAY_FILTER_USE_KEY);
            }
            return $history;
        }
        return null;
    }

    public function getSubparts(string $text): ?array
    {
        $subparts = $this->patternMatchAll('subparts', $text);
        if (!is_null($subparts)) {
            $subparts = $subparts['subpart'];
            array_walk($subparts, function (&$arg) {
                $arg = mb_strtolower($arg);
            });
            $subparts = array_values(array_filter(array_unique($subparts)));
        }
        $textures = $this->patternMatchAll('textures', $text);
        if (!is_null($textures)) {
            if (array_key_exists('texture2', $textures)) {
                $textures = array_merge($textures['texture1'], $textures['texture2']);
            } else {
                $textures = $textures['texture1'];
            }
            array_walk($textures, function (&$arg) {
                $arg = mb_strtolower($arg);
            });
            $textures = array_values(array_filter(array_unique($textures)));
        }
        if (!is_null($subparts) || !is_null($textures)) {
            return compact('subparts', 'textures');
        }
        return null;
    }

    public function getBody(string $text): string
    {
        $text = $this->formatText($text);
        $lines = explode("\n", $text);
        $index = 1;
        while ($index < count($lines)) {
            $l = explode(' ', $lines[$index]);
            $isEmptyLine = $lines[$index] === '' || $lines[$index] === '0';
            $isHeaderBFC = count($l) >= 2 && $l[1] === 'BFC' && in_array($lines[$index], ['0 BFC CERTIFY CCW', '0 BFC CERTIFY CW', '0 BFC NOCERTIFY']);
            $isHeaderMeta = count($l) >= 2 && $l[1] !== 'BFC' && in_array($l[1], app(LibrarySettings::class)->allowed_header_metas);
            $headerend = !$isEmptyLine && !($isHeaderMeta || $isHeaderBFC);
            if ($headerend) {
                break;
            }
            $index++;
        }
        return implode("\n", array_slice($lines, $index));
    }

    public function getBodyStart(string $text): int
    {
        $text = $this->formatText($text);
        $lines = explode("\n", $text);
        $index = 1;
        while ($index < count($lines)) {
            $l = explode(' ', $lines[$index]);
            $isEmptyLine = $lines[$index] === '' || $lines[$index] === '0';
            $isHeaderBFC = count($l) >= 2 && $l[1] === 'BFC' && in_array($lines[$index], ['0 BFC CERTIFY CCW', '0 BFC CERTIFY CW', '0 BFC NOCERTIFY']);
            $isHeaderMeta = count($l) >= 2 && $l[1] !== 'BFC' && in_array($l[1], app(LibrarySettings::class)->allowed_header_metas);
            $headerend = !$isEmptyLine && !($isHeaderMeta || $isHeaderBFC);
            if ($headerend) {
                break;
            }
            $index++;
        }
        return $index + 1;
    }

    public function getColours(string $text): ?array
    {
        $colors = $this->patternMatchAll('colour', $text, PREG_SET_ORDER);
        if (!is_null($colors)) {
            $colors = collect($colors);
            $colors = $colors->map(function (array $color, int $key) {
                $material = Arr::get($color, 'material');
                $color = [
                    'name' => $color['name'],
                    'code' => $color['code'],
                    'value' => $color['value'],
                    'edge' => $color['edge'],
                    'alpha' => Arr::get($color, 'alpha') == '' ? null : $color['alpha'],
                    'luminance' => Arr::get($color, 'luminance') == '' ? null : $color['luminance'],
                ];
                if (!is_null($material)) {
                    $mat = $this->patternMatch('colour_material', $material);
                    if (!is_null($mat)) {
                        $nullArray = [
                            'fabric' => false,
                            'speckle' => false,
                            'glitter' => false,
                            'material_fabric_type' => null,
                            'material_value' => null,
                            'material_alpha' => null,
                            'material_luminance' => null,
                            'material_fraction' => null,
                            'material_vfraction' => null,
                            'material_size' => null,
                            'material_maxsize' => null,
                            'material_minsize' => null
                        ];
                        if ($mat['fabric'] !== '') {
                            $matArray = [
                                'fabric' => true,
                                'material_fabric_type' => Arr::get($mat, 'f_type') == '' ? null : $mat['f_type']
                            ];
                        } elseif ($mat['speckle'] !== '') {
                            $matArray = [
                                'speckle' => true,
                                'material_value' => $mat['s_value'],
                                'material_alpha' => Arr::get($mat, 's_alpha') == '' ? null : $mat['s_alpha'],
                                'material_luminance' => Arr::get($mat, 's_luminance') == '' ? null : $mat['s_luminance'],
                                'material_fraction' => $mat['s_fraction'],
                                'material_size' => Arr::get($mat, 's_size') == '' ? null : $mat['s_size'],
                                'material_maxsize' => Arr::get($mat, 's_maxsize') == '' ? null : $mat['s_maxsize'],
                                'material_minsize' => Arr::get($mat, 's_minsize') == '' ? null : $mat['s_minsize']
                            ];
                        } else {
                            $matArray = [
                                'glitter' => true,
                                'material_value' => $mat['g_value'],
                                'material_alpha' => Arr::get($mat, 'g_alpha') == '' ? null : $mat['g_alpha'],
                                'material_luminance' => Arr::get($mat, 'g_luminance') == '' ? null : $mat['g_luminance'],
                                'material_fraction' => $mat['g_fraction'],
                                'material_vfraction' => $mat['g_vfraction'],
                                'material_size' => Arr::get($mat, 'g_size') == '' ? null : $mat['g_size'],
                                'material_maxsize' => Arr::get($mat, 'g_maxsize') == '' ? null : $mat['g_maxsize'],
                                'material_minsize' => Arr::get($mat, 'g_minsize') == '' ? null : $mat['g_minsize']
                            ];
                        }
                        $color = array_merge(array_merge($nullArray, $matArray), $color);
                    } else {
                        $material = mb_strtolower($material);
                        $color[$material] = true;
                    }
                }
                return $color;
            });
            return $colors->all();
        }

        return null;
    }

    public function basepart(string $name): ?string
    {
        $part = $this->patternMatch('base', $name);
        if (!is_array($part) || !Arr::has($part, 'base')) {
            return null;
        }

        $basepart = $part['base'];
        if (Arr::has($part, 'suffix3')) {
            $basepart .= $part['suffix1'] . $part['suffix2'];
        } elseif (Arr::has($part, 'suffix2')) {
            $basepart .= $part['suffix1'];
        }

        return $basepart;
    }

    protected function endsWithSuffix(string $name, string $code): bool
    {
        $part = $this->patternMatch('base', $name);
        if (is_null($part)) {
            return false;
        }
        if (Arr::has($part, 'suffix3')) {
            return Str::startsWith($part['suffix3'], $code);
        } elseif (Arr::has($part, 'suffix2')) {
            return Str::startsWith($part['suffix2'], $code);
        } elseif (Arr::has($part, 'suffix1')) {
            return Str::startsWith($part['suffix1'], $code);
        }
        return false;
    }

    public function patternName(string $name): bool
    {
        return $this->endsWithSuffix($name, 'p');
    }

    public function compositeName(string $name): bool
    {
        return $this->endsWithSuffix($name, 'c');
    }

    public function shortcutName(string $name): bool
    {
        return $this->endsWithSuffix($name, 'd');
    }

}
