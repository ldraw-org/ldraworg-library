<?php

namespace App\Services;

use App\Models\Part\PartRelease;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;

class PartReleaseService
{
    public function __construct(
        protected CacheService $cache
    ) {}

    public function currentRelease(): PartRelease
    {
        return $this->cache->remember(
            CacheKey::PartReleaseCurrent,
            fn() => PartRelease::current(),
        );
    }
}
