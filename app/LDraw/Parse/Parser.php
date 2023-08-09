<?php

namespace App\LDraw\Parse;

class Parser
{
    public function __construct(
        protected readonly array $patterns,
        protected readonly array $types,
        protected readonly array $type_qualifiers,
        protected readonly array $header_metas
    ) {}

    public function parse(string $part): ParsedPart 
    {
        $text = $this->formatText($part);
        $author = $this->getAuthor($text);
        $type = $this->getType($text);
        $bfc = $this->getBFC($text);
        return new ParsedPart(
            $this->getDescription($text),
            $this->getName($text),
            $author['user'] ?? null,
            $author['realname'] ?? null,
            $type['unofficial'] ?? null,
            $type['type'] ?? null,
            $type['qual'] ?? null,
            $type['releasetype'] ?? null,
            $type['release'] ?? null,
            $this->getLicense($text),
            $this->getHelp($text),
            $bfc['bfc'] == 'CERTIFY' ? $bfc['winding'] : null,
            $this->getMetaCategory($text),
            $this->getDescriptionCategory($text),
            $this->getKeywords($text),
            $this->getCmdLine($text),
            $this->getHistory($text),
            $this->getSubparts($text),
            $this->getBody($text),
            $part
        );
    }

    /**
     * fixEncoding - Ensure Correct UTF-8 encoding
     *
     * There are/were several badly encoded UTF-8 files in the library.
     * Hopefully this prevents this from happening in the future
     * 
     * @param  string $text
     * 
     * @return string
     */
    protected function fixEncoding(string $text): string
    {
        return mb_convert_encoding($text, 'UTF-8', ['ASCII', 'ISO-8859-1', 'UTF-8']);
    }
 
    /**
     * unix2dos - Change to DOS style line endings
     *
     * @param  string $text
     * 
     * @return string
     */
    public static function unix2dos(string $text): string
    {
        return preg_replace('#\R#us', "\r\n", $text);
    }
 
    /**
     * dos2unix - Change to UNIX line endings
     *
     * @param  string $text
     * 
     * @return string
     */
    public static function dos2unix(string $text): string
    {
        return preg_replace('#\R#us', "\n", $text);
    }

    /**
     * formatText - Uniformly format test
     *
     * Changes to UNIX style line endings, strips extra spaces and newlines,
     * and lower cases a type 1 line file references
     * 
     * @param  string $text
     * 
     * @return string
     */
    protected function formatText(string $text): string
    {
        $text = $this->fixEncoding($text);
        $text = self::dos2unix($text);
        $text = preg_replace('#\n{3,}#us', "\n\n", $text);
        $text = explode("\n", $text);
        foreach ($text as $index => &$line) {
            if ($index === array_key_first($text)) {
                continue;
            }
            $line = preg_replace('#\h+#u', ' ', trim($line));
            if (! empty($line) && $line[0] === '1') {
                $line = mb_strtolower($line);
            }
        }
        $text = implode("\n", $text);

        return $text;
    }

    /**
     * patternMatch
     *
     * @param string $pattern
     * @param string $text
     * 
     * @return array|null
     */
    protected function patternMatch(string $pattern, string $text): ?array
    {
        if (array_key_exists($pattern, $this->patterns) && preg_match($this->patterns[$pattern], $text, $matches)) {
            return $matches;
        }
        return null;
    }

    /**
     * patternMatchAll
     *
     * @param string $pattern
     * @param string $text
     * 
     * @return array|null
     */
    protected function patternMatchAll(string $pattern, string $text, int $flags = 0): ?array
    {
        if (array_key_exists($pattern, $this->patterns) && preg_match_all($this->patterns[$pattern], $text, $matches, $flags)) {
            return $matches;
        }
        return null;
    }

    /**
     * getSingleValueMeta
     *
     * @param string $text
     * @param string $meta
     * 
     * @return string|null
     */
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

    /**
     * getDescription - Get the file description
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getDescription(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'description');
    }

    /**
     * getName - Get the Name: meta value
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getName(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'name');
    }

    /**
     * getLicense - Get the !LICENSE line
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getLicense(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'license');
    }

    /**
     * getCmdLine - Get !CMDLINE value
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getCmdLine(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'cmdline');
    }

    /**
     * getMetaCategory
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getMetaCategory(string $text): ?string
    {
        return $this->getSingleValueMeta($text, 'category');
    }

    /**
     * getDescriptionCategory
     *
     * @param string $text
     * 
     * @return string|null
     */
    public function getDescriptionCategory(string $text): ?string
    {
        $d = $this->getSingleValueMeta($text, 'description');
        if (!is_null($d)) {
            $dwords = explode(' ', $d);
            $category = trim($dwords[0]);
            //Remove prefixes
            $category = str_replace(['~', '|', '=', '_'], '', $category);
            if ($category !== '') {
                return $category;
            } 
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
  
    /**
     * getKeywords
     *
     * @param string $text
     * 
     * @return array|null
     */
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

    /**
     * getType
     *
     * @param string $text
     * 
     * @return array|null
     */
    public function getType(string $text): ?array
    {
        if (array_key_exists('type', $this->patterns)) {
            $pattern = str_replace(['###PartTypes###', '###PartTypesQualifiers###'], [implode('|', $this->types), implode('|', $this->type_qualifiers)], $this->patterns['type']);
            
            if (preg_match($pattern, $text, $matches)) {
                $t = ['unofficial' => false, 'type' => $matches['type'], 'qual' => '', 'releasetype' => '', 'release' => ''];
                if (array_key_exists('unofficial', $matches) && $matches['unofficial'] !== '') {
                    $t['unofficial'] = true;
                }
                if (array_key_exists('qual', $matches)) {
                    $t['qual'] = $matches['qual'];
                }
                if (array_key_exists('releasetype', $matches)) {
                    $t['releasetype'] = $matches['releasetype'];
                }
                if (array_key_exists('release', $matches)) {
                    $t['release'] = $matches['release'];
                }
                if (array_key_exists('releasetype', $matches) && $matches['releasetype'] == 'ORIGINAL') {
                    $t['release'] = 'original';
                }
                return $t;
            }
        }
        return null;
    }

    /**
     * getHelp
     *
     * @param string $text
     * 
     * @return array|null
     */
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

    /**
     * getBFC
     *
     * @param string $text
     * 
     * @return array|null
     */
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

    /**
     * getHistory
     *
     * @param string $text
     * 
     * @return array|null
     */
    public function getHistory(string $text): ?array
    {
        $history = $this->patternMatchAll('history', $text, PREG_SET_ORDER);
        if (!is_null($history)) {
            foreach($history as &$hist) {
                $hist = array_filter($hist, 'is_string', ARRAY_FILTER_USE_KEY);
            }
            return $history;    
        }
        return null;
    }

    /**
     * getSubparts
     *
     * @param string $text
     * 
     * @return array|null
     */
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
    
    /**
     * getBody
     *
     * @param string $text
     * 
     * @return string
     */
    public function getBody(string $text): string {
        $lines = explode("\n", $text);
        $index = 0;
        while ($index < count($lines)) {
            $l = explode(' ', $lines[$index]);
            if ((count($l) >= 2 && $l[0] === '0' && in_array($l[1], $this->header_metas, true)) ||
                ($index === 0 && count($l) >= 2 && $l[0] === '0') ||
                in_array($lines[$index], ['0', '', '0 BFC CERTIFY CCW', '0 BFC CERTIFY CW', '0 BFC NOCERTIFY'])) {
                $index++;
            } else {
                break;
            }
        }
        return implode("\n", array_slice($lines, $index));
    }
}