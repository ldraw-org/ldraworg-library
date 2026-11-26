<?php

namespace App\Services\LDConfig;

use App\Models\Avatar;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;

class AvatarLookup
{
    public function __construct(
        protected CacheService $cache,
    ) {
    }

    /**
     * @return array<string, string> avatar part filename keyed by category
     */
    public function parts(): array
    {
        return $this->cache->remember(
            CacheKey::AvatarParts,
            fn () => Avatar::pluck('part', 'category')->all()
        );
    }
}
