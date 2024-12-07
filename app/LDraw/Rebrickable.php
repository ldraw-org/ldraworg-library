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

    public function __construct() {
        $this->api_key = config('ldraw.rebrickable_api_key', '');
    }

    protected function makeApiCall(string $url): ?array
    {
        if (Cache::has('rebrickable_timeout')) {
            time_sleep_until(Cache::get('rebrickable_timeout') + 1);
        }

        Cache::put('rebrickable_timeout', now()->addSeconds($this->limit)->format('U'), now()->addSeconds($this->limit + 1));

        $response = Http::withHeaders([
            'Authorization' => "key {$this->api_key}",
        ])
        ->acceptJson()
        ->get($url);

        if ($response->status() == 429) {
            dd($response);
        } elseif (!$response->successful()) {
            return null;
        }

        if (!is_null($response->json('next')) && !is_null($response->json('results'))) {
            $result = array_merge($response->json('results'), $this->makeApiCall($response->json('next')));
            return $result;
        } elseif (!is_null($response->json('results'))) {
            return $response->json('results');
        }
        return $response->json();
    }

    public function getSetParts(string $setnumber): ?Collection
    {
        $parts = $this->makeApiCall("{$this->api_url}/sets/{$setnumber}/parts/?inc_minifig_parts=1");
        if (is_null($parts)) {
            return null;
        }
        $parts = collect($parts);
        $parts = $parts->map(
            fn (array $part, int $key) =>
            [
                'rb_part_number' => $part['part']['part_num'],
                'rb_part_name' => $part['part']['name'],
                'rb_part_url' => $part['part']['part_url'],
                'color_name' => $part['color']['name'],
                'ldraw_color_number' => $part['color']['external_ids']['LDraw']['ext_ids'][0] ?? null,
                'ldraw_part_number' => $part['part']['external_ids']['LDraw'][0] ?? null,
                'bricklink_part_number' => $part['part']['external_ids']['BrickLink'][0] ?? null,
                'quantity' => $part['quantity'],
                'print_of' => $part['part']['print_of'] ?? null,
            ]
        );
        return $parts;
    }

    public function getSet(string $setnumber): ?array
    {
        return $this->makeApiCall("{$this->api_url}/sets/{$setnumber}/");
    }

    public function getPart(string $partnumber): ?array
    {
        $part = $this->makeApiCall("{$this->api_url}/parts/{$partnumber}/");
        if (is_null($part)) {
            return null;
        }
        return $this->makePartArray($part);
    }

    public function getParts(array $partnumbers): ?Collection
    {
        $parts = implode(',', $partnumbers);
        $parts = $this->makeApiCall("{$this->api_url}/parts/?part_nums={$parts}");
        if (is_null($parts)) {
            return null;
        }
        $parts = collect($parts);
        $parts = $parts->map(
            fn (array $part, int $key) =>
            $this->makePartArray($part)
        );
        return $parts;
    }

    public function getPartBySearch(string $search): ?array
    {
        $part = $this->makeApiCall("{$this->api_url}/parts/?search={$search}");
        if (is_null($part) || count($part) !== 1) {
            return null;
        }
        return $this->makePartArray($part[0]);
    }

    protected function makePartArray(array $api_part): array
    {
        return [
            'rb_part_number' => $api_part['part_num'],
            'rb_part_name' => $api_part['name'],
            'rb_part_url' => $api_part['part_url'],
            'rb_part_img_url' => $api_part['part_img_url'],
            'ldraw_part_number' => $api_part['external_ids']['LDraw'][0] ?? null,
            'bricklink_part_number' => $api_part['external_ids']['BrickLink'][0] ?? null,
            'print_of' => $api_part['print_of'] ?? null,
        ];
    }
}
