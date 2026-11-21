---
paths:
  - 'app/Services/**'
---

# Services

## Cache through CacheKey
Add a case to `App\Services\Cache\CacheKey` (with its `reset`/`warm` behavior) and read through an injected `CacheService::remember(CacheKey::X, fn)`. Do not call `Cache::` with ad-hoc string keys.
