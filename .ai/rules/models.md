---
paths:
  - 'app/Models/**'
---

# Models

## Model attributes, not properties
Mark models `#[Unguarded]` (User uses `#[Fillable]`); never add `$fillable`/`$guarded`. Define scopes as `#[Scope] protected function name(Builder $query): void` without a `scope` prefix. Wire observers with `#[ObservedBy]` and custom collections with `#[CollectedBy]`.

## snake_case relationships
Name multi-word relationships in snake_case (e.g. `part_events()`) and pass explicit key arguments. Reuse the `app/Models/Traits` mixins (`HasUser`, `HasPart`, `HasPartRelease`, ...) instead of redeclaring those relations.
