<?php

namespace App\Services\External;

use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Rebrickable
{
    /**
     * Minimum seconds between the end of one Rebrickable call and the start of the next.
     * The API allows 1 call/sec; this must stay above 1.
     */
    protected const float MIN_SECONDS_BETWEEN_CALLS = 1.1;

    /** Longest a call will wait for its slot (e.g. after a 429 Retry-After) before giving up. */
    protected const int MAX_WAIT_SECONDS = 30;

    protected const int CONNECT_TIMEOUT_SECONDS = 10;
    protected const int REQUEST_TIMEOUT_SECONDS = 30;

    /** Must exceed the longest time the lock is held: MAX_WAIT + CONNECT + REQUEST timeouts. */
    protected const int LOCK_TTL_SECONDS = 90;

    /** How long a caller waits to acquire the lock while other processes are calling. */
    protected const int LOCK_BLOCK_SECONDS = 120;

    protected const string LOCK_KEY = 'rebrickable_api_throttle';
    protected const string NEXT_CALL_KEY = 'rebrickable_api_next_call_at';

    protected string $apiUrl = 'https://rebrickable.com/api/v3/lego';
    protected string $apiKey;
    protected int $maxAttempts = 3;

    public function __construct()
    {
        $this->apiKey = config('ldraw.rebrickable_api_key', '');
    }

    protected function makeApiCall(string $url, bool $firstPageOnly = false, bool $throwOnFailure = false): Collection
    {
        $all = collect([]);
        $currentUrl = $url;

        while ($currentUrl) {
            $response = $this->makeRequest($currentUrl);

            if (!$response) {
                if ($throwOnFailure) {
                    throw new RebrickableRequestFailed("Rebrickable request failed: {$currentUrl}");
                }
                break;
            }

            $isPaginated = array_key_exists('results', $response);

            if (!$isPaginated) {
                return collect($response);
            }

            $all = $all->merge($response['results']);

            if (empty($response['next'])) {
                break;
            }

            if ($firstPageOnly) {
                if (array_key_exists('count', $response)) {
                    $all->put('count', $response['count']);
                }
                break;
            }
            $currentUrl = $response['next'];
        }

        return $all;
    }

    /**
     * Retries connection errors, 429s and 5xx responses. Every attempt goes through send(),
     * so retries are rate limited like any other call.
     */
    protected function makeRequest(string $url): ?array
    {
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $response = $this->send($url);
            } catch (ConnectionException $e) {
                Log::warning('Rebrickable connection failed', ['url' => $url, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                continue;
            } catch (Exception $e) {
                Log::error('Rebrickable request exception', ['url' => $url, 'error' => $e->getMessage()]);
                return null;
            }

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Rebrickable API failed', [
                'url' => $url,
                'attempt' => $attempt,
                'code' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->status() !== 429 && !$response->serverError()) {
                return null;
            }
        }

        return null;
    }

    /**
     * Sends a single request while holding a global lock in the cache store (Redis), so only one
     * Rebrickable call is in flight across all processes and each starts at least
     * MIN_SECONDS_BETWEEN_CALLS after the previous one finished.
     *
     * @throws LockTimeoutException
     * @throws RebrickableRequestFailed if the required wait exceeds MAX_WAIT_SECONDS
     * @throws ConnectionException
     */
    protected function send(string $url): Response
    {
        return Cache::lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS)->block(self::LOCK_BLOCK_SECONDS, function () use ($url): Response {
            $wait = (float) Cache::get(self::NEXT_CALL_KEY, 0) - microtime(true);
            if ($wait > self::MAX_WAIT_SECONDS) {
                throw new RebrickableRequestFailed("Rebrickable is backing off for {$wait} more seconds");
            }
            if ($wait > 0) {
                usleep((int) ceil($wait * 1_000_000));
            }

            $retryAfter = 0;
            try {
                $response = Http::withHeaders(['Authorization' => "key {$this->apiKey}"])
                    ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->acceptJson()
                    ->get($url);
                if ($response->status() === 429) {
                    $retryAfter = max(0, (int) $response->header('Retry-After'));
                }
                return $response;
            } finally {
                // Set after the request completes so the gap between calls always exceeds the minimum
                Cache::put(
                    self::NEXT_CALL_KEY,
                    microtime(true) + max(self::MIN_SECONDS_BETWEEN_CALLS, $retryAfter),
                    now()->addHour()
                );
            }
        });
    }

    /**
     * @throws RebrickableRequestFailed so a failed or partial fetch is never cached or treated as the full list
     */
    public function getSetParts(string $setnumber): Collection
    {
        return Cache::remember(
            "rebrickable_set_parts_{$setnumber}",
            3600,
            fn () => $this->makeApiCall(
                "{$this->apiUrl}/sets/{$setnumber}/parts/?inc_minifig_parts=1&inc_part_details=1&inc_color_details=0",
                throwOnFailure: true
            )
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

    public function getThemes(bool $fresh = false): Collection
    {
        if ($fresh) {
            Cache::forget('rebrickable_themes');
        }

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
}
