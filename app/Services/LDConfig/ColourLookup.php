<?php

namespace App\Services\LDConfig;

use App\Models\LdrawColour;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;

class ColourLookup
{
    public function __construct(
        protected CacheService $cache,
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function codes(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourCodes,
            fn () => LdrawColour::pluck('code')->all()
        );
    }

    /**
     * @return array<int, string> colour swatch label HTML keyed by code
     */
    public function options(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourOptions,
            fn () => LdrawColour::select('id', 'code', 'name', 'value')
                ->orderBy('code')
                ->get()
                ->mapWithKeys(
                    fn (LdrawColour $color) =>
                    [$color->code => "<span class=\"{$color->labelTextColor()} rounded px-2 py-1\" style=\"background-color: {$color->value}\">{$color->code} - {$color->name}</span>"]
                )
                ->all()
        );
    }

    /**
     * @return array<int, ?int> Rebrickable colour id keyed by LDraw colour code
     */
    public function codesToRebrickable(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourCodesToRebrickable,
            fn () => LdrawColour::pluck('rebrickable_id', 'code')->all()
        );
    }

    /**
     * LDraw colour code keyed by Rebrickable colour id. When several codes share a Rebrickable colour
     * the first one wins.
     *
     * @return array<int, int>
     */
    public function rebrickableToCodes(): array
    {
        $codes = [];
        foreach ($this->codesToRebrickable() as $code => $rebrickableId) {
            if (!is_null($rebrickableId) && !isset($codes[$rebrickableId])) {
                $codes[$rebrickableId] = $code;
            }
        }

        return $codes;
    }
}
