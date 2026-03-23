<?php

namespace App\Services\Cache;

use App\Enums\PartType;
use App\Models\LdrawColour;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function reset(CacheKey $key): void
    {
        $key->reset();
    }

    public function resetAll(): void
    {
        foreach (CacheKey::cases() as $key) {
            $key->reset();
        }
    }
    public function warm(CacheKey $key): void
    {
        $key->warm();
    }

    public function warmAll(): void
    {
        foreach (CacheKey::cases() as $key) {
            $key->warm();
        }
    }

    public function remember(CacheKey $key, \Closure $callback)
    {
        return Cache::rememberForever($key->value, $callback);
    }
}
