<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;

class UserList
{
    public function __construct(
        protected CacheService $cacheService,
    ) {}
    public function userOptions(): array
    {
        return $this->cacheService->remember(
            CacheKey::UserOptions,
            fn() => User::select('id', 'author_string')->orderBy('author_string')->pluck('author_string', 'id')->all(),
        );
    }
}
