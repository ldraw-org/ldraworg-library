---
paths:
  - 'tests/**'
---

# Tests

## Factories, automatic seed, faked HTTP
`TestCase` sets `$seed = true`, so roles, permissions and the admin user already exist; do not seed by hand and create other data with factories. Call `Http::preventStrayRequests()` and fake external APIs (use the `fakeRebrickableCsv()`/`rebrickableArchive()` helpers); use real services rather than Mockery. Name tests with `it('...')`.
