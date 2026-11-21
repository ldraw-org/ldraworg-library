---
paths:
  - 'app/Services/**/Enums/**'
---

# Services Enums

## Backed enums with CanBeOption
Use `use CanBeOption;` and override `customLabel()` instead of writing `label()`/`options()`. Put per-case behavior in `match ($this)` methods.
