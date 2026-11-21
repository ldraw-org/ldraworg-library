---
paths:
  - 'app/**'
---

# App

## Use the Auth facade
Write `Auth::user()` / `Auth::check()`, not the `auth()` helper.

## Icons through LibraryIcon
Add a case to `App\Enums\LibraryIcon` and use `LibraryIcon::X` in PHP or `<x-library-icon>` in Blade. Never hard-code `mdi-*` icon strings.
