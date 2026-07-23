---
name: Plugin migration indexes
description: Cross-database idempotency guidance for isolated AwanTools plugin migrations
---

Plugin lifecycle migrations must be safe to run more than once on both SQLite and
MySQL. `CREATE INDEX IF NOT EXISTS` is not portable enough for this platform:
MySQL does not support the same syntax, while repeat activation must not fail.

**Why:** AwanTools activates plugins from the admin lifecycle and supports both
SQLite and MySQL deployments, so a migration that works only on the local SQLite
database can break installation on shared hosting.

**How to apply:** Create tables with `IF NOT EXISTS`, then create indexes inside a
small exception boundary that ignores only known duplicate/already-exists errors
and rethrows all other database failures.