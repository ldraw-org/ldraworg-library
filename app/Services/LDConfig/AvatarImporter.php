<?php

namespace App\Services\LDConfig;

use App\Models\Avatar;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;

class AvatarImporter
{
    public function __construct(
        protected LDConfigFile $ldconfig,
        protected CacheService $cache,
    ) {
    }

    public function import(): void
    {
        $avatars = $this->ldconfig->entries()
            ->where('meta', 'avatar')
            ->map(function (array $avatar) {
                return [
                    'category' => $avatar['category'],
                    'description' => $avatar['description'],
                    'part' => $avatar['file'],
                    'matrix' => "{$avatar['a']} {$avatar['b']} {$avatar['c']} " .
                                "{$avatar['d']} {$avatar['e']} {$avatar['f']} " .
                                "{$avatar['g']} {$avatar['h']} {$avatar['i']}"
                ];
            })
            ->all();
        Avatar::upsert($avatars, uniqueBy: 'category', update: ['part', 'matrix', 'description']);
        $this->cache->reset(CacheKey::AvatarParts);
        $this->cache->warm(CacheKey::AvatarParts);
    }
}
