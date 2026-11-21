---
paths:
  - 'app/Policies/**'
---

# Policies

## Permission enum wrapped by policies
Reference `App\Enums\Permission` cases, never permission strings. Put model-related checks in a Policy method combining `$user->can(Permission::X)` with model or settings state, and call the policy ability from UI code.
