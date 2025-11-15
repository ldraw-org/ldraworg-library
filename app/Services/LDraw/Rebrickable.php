<?php

namespace App\Services\LDraw;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Rebrickable
{
    protected int $rateLimitSeconds = 1;
    protected string $apiUrl = 'https://rebrickable.com/api/v3/lego';
    protected string $apiKey;
    protected int $maxRetries = 3;

    public function __construct()
    {
        $this->apiKey = config('ldraw.rebrickable_api_key', '');
    }

    protected function enforceRateLimit(): void
    {
        $lockKey = 'rebrickable_rate_limit_lock';
        $last = Cache::get($lockKey, 0);
    
        $elapsed = microtime(true) - $last;
        if ($elapsed < $this->rateLimitSeconds) {
            usleep((int)(($this->rateLimitSeconds - $elapsed) * 1_000_000));
        }
    
        Cache::put($lockKey, microtime(true), now()->addSeconds(10));
    }

    protected function makeApiCall(string $url, bool $firstPageOnly = false): Collection
    {
        $all = collect([]);
        $currentUrl = $url;

        while ($currentUrl) {

            $this->enforceRateLimit();

            $response = $this->makeRequest($currentUrl);

            if (!$response) {
                break;
            }

            $isPaginated = array_key_exists('results', $response);

            if (!$isPaginated) {
                return collect($response);
            }

            $all = $all->merge($response['results']);

            if ($firstPageOnly || empty($response['next'])) {
                break;
            }

            $currentUrl = $response['next'];
        }

        return $all;
    }

    protected function makeRequest(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                    'Authorization' => "key {$this->apiKey}",
                ])
                ->retry($this->maxRetries, 2000, function ($ex, $request) {
                    return true;
                })
                ->timeout(30)
                ->acceptJson()
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Rebrickable API failed', [
                    'url'   => $url,
                    'code'  => $response->status(),
                    'body'  => $response->body(),
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Rebrickable request exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }


    public function getSetParts(string $setnumber): Collection
    {
        return Cache::remember(
            "rebrickable_set_parts_{$setnumber}",
            3600,
            fn () => $this->makeApiCall("{$this->apiUrl}/sets/{$setnumber}/parts/?inc_minifig_parts=1&inc_part_details=1&inc_color_details=0&page_size=1000")
        );
    }

    public function getColors(): Collection
    {
        return Cache::remember(
            "rebrickable_colors",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/colors/")
        );
    }

    public function getSet(string $setnumber): Collection
    {
        return Cache::remember(
            "rebrickable_set_{$setnumber}",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/sets/{$setnumber}/")
        );
    }

    public function getTheme(int $themeid): Collection
    {
        return Cache::remember(
            "rebrickable_theme_{$themeid}",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/themes/{$themeid}/")
        );
    }

    public function getThemes(): Collection
    {
        return Cache::remember(
            "rebrickable_themes",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/themes/")
        );
    }

    public function getPart(string $partnumber): Collection
    {
        return Cache::remember(
            "rebrickable_part_{$partnumber}",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/parts/{$partnumber}/")
        );
    }

    public function getParts(array $parameters, bool $firstPageOnly = false): Collection
    {
        $query = $this->buildQueryString($parameters);

        return Cache::remember(
            "rebrickable_parts_" . md5($query) . "_first_{$firstPageOnly}",
            3600,
            fn () => $this->makeApiCall("{$this->apiUrl}/parts/?{$query}", $firstPageOnly)
        );
    }

    public function getPartColor(string $partnumber, bool $firstPageOnly = false): Collection
    {
        return Cache::remember(
            "rebrickable_part_colors_{$partnumber}_first_{$firstPageOnly}",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/parts/{$partnumber}/colors/", $firstPageOnly)
        );
    }

    public function getPartColorSets(string $partnumber, string $color_id, bool $firstPageOnly = false): Collection
    {
        return Cache::remember(
            "rebrickable_part_color_sets_{$partnumber}_{$color_id}_first_{$firstPageOnly}",
            86400,
            fn () => $this->makeApiCall("{$this->apiUrl}/parts/{$partnumber}/colors/{$color_id}/sets", $firstPageOnly)
        );
    }


    protected function buildQueryString(array $parameters): string
    {
        $formatted = array_map(
            fn ($v) => is_array($v) ? implode(',', $v) : $v,
            $parameters
        );

        return http_build_query($formatted);
    }


    public function clearRateLimit(): void
    {
        Cache::forget('rebrickable_rate_limit_lock');
    }
}