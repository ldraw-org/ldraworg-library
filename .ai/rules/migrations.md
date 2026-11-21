---
paths:
  - 'database/migrations/**'
---

# Migrations

## Enum columns and foreign keys
Store enums in string or integer columns cast to a backed enum on the model; never use `->enum()` columns. Declare foreign keys with `foreignIdFor(Model::class)`.
