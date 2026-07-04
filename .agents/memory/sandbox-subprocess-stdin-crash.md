---
name: Sandbox subprocess stdin crash
description: A bash-tool crash mode seen when a subprocess reads redirected stdin (e.g. database bootstrap commands), and the workaround.
---

The `bash` tool can crash with an error like `handle_syscall...openat: get fd path ffffffff` when a subprocess reads from redirected stdin — observed with `mariadbd --bootstrap < schema.sql`-style commands.

**Why:** appears to be a sandbox/FD-handling limitation specific to the `bash` tool when stdin is piped/redirected into certain long-running or privileged subprocesses.

**How to apply:** If a bash command that pipes/redirects stdin into a subprocess crashes the shell, retry the same operation using the `code_execution` tool's `execSync` (Node child_process) instead of the `bash` tool. This has been reliable for MariaDB bootstrap/schema-load style operations.
