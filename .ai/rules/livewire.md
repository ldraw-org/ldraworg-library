---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Permission enum wrapped by policies
Reference `App\Enums\Permission` cases, never permission strings. Put model-related checks in a Policy method combining `$user->can(Permission::X)` with model or settings state, and call the policy ability from UI code.

## Authorize full pages in mount
Full-page components call `$this->authorize(...)` in `mount()`. Hide unauthorized Filament actions with `->visible(fn () => Auth::user()?->can(...))`.

## Class-based full-page Livewire
Interactive pages are class-based components in `app/Livewire/<Area>/` routed with `Route::get(..., Component::class)`. Set the layout with `#[Layout('components.layout.<x>')]` and the page title with `<x-slot:title>` in the view, not `#[Title]`. Controllers are invokable and only for non-interactive responses.
