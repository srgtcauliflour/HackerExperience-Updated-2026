---
name: Legacy MySQL5-era codebase quirks
description: Behavioral assumptions old PHP5/MySQL5 game codebases make about SQL mode and seed data that will break under modern MariaDB defaults.
---

Old PHP5-era codebases (e.g. games/CMSs from the MySQL5 era) frequently rely on:

1. **Relaxed sql_mode** — code does `INSERT INTO table VALUES('', col2, col3, ...)` expecting the empty string to be coerced to the next auto-increment value. Modern MariaDB defaults (with `STRICT_TRANS_TABLES`) reject this. Fix at the server level (`sql_mode=NO_ENGINE_SUBSTITUTION`, no `STRICT_TRANS_TABLES`) rather than patching every call site — there are usually many (cron jobs, war/battle logic, process classes, etc.) and patching them all is high risk for a legacy codebase you don't want to rewrite.

2. **Assumed seed data** — schema-only dumps (e.g. `game.sql` with structure but no rows) can be missing rows the app hard-depends on (e.g. an NPC bank row, a "round" row for game state). Symptoms show up as fatal errors deep in gameplay logic (e.g. account creation) that look like code bugs but are actually just missing reference data. Check for foreign-key-like lookups (`WHERE id = 1` on reference tables) failing silently/fatally before assuming the PHP logic itself is broken.

**Why:** These distinctions matter because the "correct" fix is server/data configuration, not a rewrite of business logic — rewriting risks introducing new bugs in a large, unfamiliar legacy codebase.

**How to apply:** When importing/running an old MySQL5-era PHP app on modern MariaDB, first check the SQL mode and confirm minimal seed/reference rows are present before deep-diving into "fatal error" stack traces.
