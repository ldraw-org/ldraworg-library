---
paths:
  - 'resources/views/**'
---

# Views

## Blade components only
Build partials as anonymous `<x-...>` components with `@props`; never use `@include`. Add a class in `app/View/Components` only when the view needs computed data.

## Icons through LibraryIcon
Add a case to `App\Enums\LibraryIcon` and use `LibraryIcon::X` in PHP or `<x-library-icon>` in Blade. Never hard-code `mdi-*` icon strings.
