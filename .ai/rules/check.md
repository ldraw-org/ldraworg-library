---
paths:
  - 'app/Services/Check/**'
---

# Check

## Part checks extend BaseCheck
A new check is a `BaseCheck` subclass whose `check()` yields `$this->error(PartError::X)`. Limit scope with the `PartOnly`/`ParsedPartOnly` traits and register it in the matching array in `PartChecker`.
