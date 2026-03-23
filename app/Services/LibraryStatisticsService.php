<?php

namespace App\Services;

use App\Enums\PartType;
use App\Models\Part\Part;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;

class LibraryStatisticsService
{
    public function __construct(
        protected CacheService $cache
    ) {}

    public function officialPartCount(bool $live = false): int
    {
        if ($live) {
            return $this->computeOfficialPartCount();
        }

        return $this->cache->remember(
            CacheKey::OfficialPartCount,
            fn () => $this->computeOfficialPartCount()
        );
    }
    private function computeOfficialPartCount(): int
    {
        return Part::official()
            ->where('type', PartType::Part)
            ->whereNull('type_qualifier')
            ->whereNotLike('filename', 'parts/t%.dat')
            ->activeParts()
            ->count();
    }
}
