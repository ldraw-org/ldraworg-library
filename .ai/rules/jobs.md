---
paths:
  - 'app/Jobs/**'
---

# Jobs

## Thin jobs configured by attributes
Jobs delegate to a service injected into `handle()`. Set queue and timeout with `#[Queue('maintenance'|'release')]` and `#[Timeout(n)]`, and use only `use Queueable;`. Do not add `$queue`/`$timeout` properties.
