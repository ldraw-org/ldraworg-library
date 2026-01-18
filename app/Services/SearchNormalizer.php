<?php

namespace App\Services;

class SearchNormalizer
{
    /**
     * Normalize a raw user search string for MySQL BOOLEAN FULLTEXT.
     * Prepends + to terms by default (AND behavior).
     *
     * Examples:
     *  "brick 1x2" -> "+brick +1x2"
     *  "type 1 -obsolete" -> "+type +1 -obsolete"
     *  '"Charles The Great"' -> '+"Charles The Great"'
     */
    public static function booleanMode(string $input): string
    {
        $input = trim($input);

        if (empty($input)) {
            return '';
        }

        $tokens = [];
        $pattern = '/
            (\([^(]+\)|"[^"]+"|\S+)   # match quoted phrases OR non-space sequences
        /x';

        preg_match_all($pattern, $input, $matches);

        foreach ($matches[0] as $token) {
            $token = trim($token);

            // Normalize token (example: collapse whitespace inside phrases, fix 1 x 2 -> 1x2)
            $token = self::normalizeToken($token);

            // Skip if token is empty after normalization
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }

            // If token already starts with + or -, leave it
            if (preg_match('/^[\+\-]/', $token) && mb_strlen($token) > 2) {
                $tokens[] = $token;
            } else {
                $tokens[] = '+' . $token; // default AND
            }
        }
        return implode(' ', $tokens);
    }

    /**
     * Normalize individual token
     * Example transformations:
     *  - lowercase everything
     *  - collapse internal spaces
     *  - normalize dimensions (optional)
     */
    protected static function normalizeToken(string $token): string
    {
        // Remove outer quotes but preserve phrase
        $isPhrase = false;
        if (str_starts_with($token, '"') && str_ends_with($token, '"')) {
            $isPhrase = true;
            $token = substr($token, 1, -1);
        }

        $token = trim($token);

        // Example: collapse multiple spaces
        $token = preg_replace('/\s+/', ' ', $token);

        // Optional: normalize dimensions 1 x 2 -> 1x2
        $token = preg_replace('/(\d)\s*x\s*(\d)/i', '$1x$2', $token);

        // Optional: normalize decimal numbers 3.18 -> 3_18
        $token = preg_replace('/(\d+)\.(\d+)/', '$1p$2', $token);

        // Lowercase for consistency
        $token = mb_strtolower($token);

        if ($isPhrase) {
            $token = '"' . $token . '"';
        }

        return $token;
    }

    /**
     * Fallback for LIKE queries
     */
    public static function like(string $input): string
    {
        $token = self::normalizeToken($input);
        return '%' . $token . '%';
    }
}