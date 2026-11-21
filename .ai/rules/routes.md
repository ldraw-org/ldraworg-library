---
paths:
  - routes/web.php
---

# Routes

## Class-based full-page Livewire
Interactive pages are class-based components in `app/Livewire/<Area>/` routed with `Route::get(..., Component::class)`. Set the layout with `#[Layout('components.layout.<x>')]` and the page title with `<x-slot:title>` in the view, not `#[Title]`. Controllers are invokable and only for non-interactive responses.
