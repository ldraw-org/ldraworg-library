<?php

namespace App\LDraw;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Rebrickable
{
    protected int $limit = 1;
    protected string $api_url = 'https://rebrickable.com/api/v3/lego';
    protected string $api_key;

    public function __construct()
    {
        $this->api_key = config('ldraw.rebrickable_api_key', '');
    }

    protected function makeApiCall(string $url): Collection
    {
        if (Cache::has('rebrickable_timeout')) {
            time_sleep_until(Cache::get('rebrickable_timeout'));
        }

        Cache::put('rebrickable_timeout', now()->addSeconds($this->limit + 1)->format('U'), now()->addSeconds($this->limit + 1));

        try {
            $response = Http::withHeaders([
                'Authorization' => "key {$this->api_key}",
            ])
            ->acceptJson()
            ->get($url);

            if ($response->status() == 429) {
                dd($response);
            } elseif (!$response->successful()) {
                return collect([]);
            }

            if (!is_null($response->json('next')) && !is_null($response->json('results'))) {
                $result = collect($response->json('results'))->merge($this->makeApiCall($response->json('next')));
                return $result;
            } elseif (!is_null($response->json('results'))) {
                return collect($response->json('results'));
            }
            return collect($response->json());
        } catch (\Exception $e) {
            return collect([]);
        }

    }

    public function getSetParts(string $setnumber): Collection
    {
        return $this->makeApiCall("{$this->api_url}/sets/{$setnumber}/parts/?inc_minifig_parts=1&page_size=2000");
    }

    public function getSet(string $setnumber): Collection
    {
        return $this->makeApiCall("{$this->api_url}/sets/{$setnumber}/");
    }

    public function getPart(string $partnumber): Collection
    {
        return $this->makeApiCall("{$this->api_url}/parts/{$partnumber}/");
    }

    public function getParts(array $parameters): Collection
    {
        foreach ($parameters as &$parameter) {
            if (is_array($parameter)) {
                $parameter = implode(',', $parameter);
            }
        }
        $parameters = http_build_query($parameters);
        return $this->makeApiCall("{$this->api_url}/parts/?{$parameters}");
    }
}
