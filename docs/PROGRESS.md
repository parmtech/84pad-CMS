# 84PAD Core — Resume Checkpoint

> **Source of truth:** GitHub issue states + this file. Do not infer completion from chat messages.
> Last updated: 2026-10-21 (Australia/Sydney).
> **Current phase:** Core 0.1 — foundation.
> **Next issue:** [#1 C01 — Bootstrap Laravel 12 foundation](https://github.com/manbir6-ux/84pad-core/issues/1).
> **Runnable:** NOT VERIFIED. C01 partial Laravel source scaffold is now committed on a feature branch; dependencies have not been installed or executed.
> **Environment test status:** NOT RUN on GitHub scaffold. The working container has PHP 8.4.24, but Composer, mbstring and a SQLite driver were not available in the checked runtime. No Laravel app/test command succeeded.

## Work checklist

| ID | Task | State | Proof |
|---|---|---|---|
| [C01](https://github.com/manbir6-ux/84pad-core/issues/1) | Laravel 12 scaffold | **In progress — source committed, runtime unverified** | [Feature branch](https://github.com/manbir6-ux/84pad-core/tree/core/C01-laravel-foundation): 23 changed files; not yet merged to main |
| [C02](https://github.com/manbir6-ux/84pad-core/issues/2) | Config and secret hygiene | Not started | none |
| [C03](https://github.com/manbir6-ux/84pad-core/issues/3) | Installer preflight checks | Not started | none |
| [C04](https://github.com/manbir6-ux/84pad-core/issues/4) | One-time owner installer | Not started | none |
| [C05](https://github.com/manbir6-ux/84pad-core/issues/5) | Authentication | Not started | none |
| [C06](https://github.com/manbir6-ux/84pad-core/issues/6) | Authorization | Not started | none |
| [C07](https://github.com/manbir6-ux/84pad-core/issues/7) | Control Studio dashboard | Not started | none |
| [C08](https://github.com/manbir6-ux/84pad-core/issues/8) | Plugin manifest | Not started | none |
| [C09](https://github.com/manbir6-ux/84pad-core/issues/9) | Plugin discovery | Not started | none |
| [C10](https://github.com/manbir6-ux/84pad-core/issues/10) | Plugin activation/deactivation | Not started | none |
| [C11](https://github.com/manbir6-ux/84pad-core/issues/11) | Hooks API | Not started | none |
| [C12](https://github.com/manbir6-ux/84pad-core/issues/12) | Demo plugin | Not started | none |
| [C13](https://github.com/manbir6-ux/84pad-core/issues/13) | Database/plugin migration strategy | Not started | none |
| [C14](https://github.com/manbir6-ux/84pad-core/issues/14) | Update/recovery interfaces | Not started | none |
| [C15](https://github.com/manbir6-ux/84pad-core/issues/15) | QA and handoff | Not started | none |

## Work log

### 2026-10-21
- Verified GitHub repo `manbir6-ux/84pad-core` exists, remains **private**, and connector has push access.
- Previous commit created initial `README.md` with agreed Core 0.1 scope.
- Opened C01–C15 as individually testable GitHub issues.
- Added this persistent checkpoint and companion workflow.
- Added initial Laravel 12 source scaffold to `core/C01-laravel-foundation` (23 changed files): Composer manifest, bootstrapping, routing, basic page, test stubs, safe example environment and runtime directory stubs.
- Compared feature branch with `main`: 1 commit ahead, 23 changed files; the foundation is **not merged**.
- PHP 8.4.24 is present locally; Composer, mbstring and SQLite driver are missing in this runtime, so Laravel install and app tests remain **unverified**.
- Initial scaffold remains incomplete for full Laravel conventions (e.g. config and user model/migrations); expand foundation and verify before closing C01.
- **No production-ready Core, installer, login, plugin manager or successful Laravel runtime tests recorded yet.**

## Session resume instructions

1. Read `docs/PROGRESS.md`, `docs/WORKFLOW.md`, and current README.
2. Inspect current repository files, open issues and recent commits; don't assume this markdown is fully synchronized.
3. Pick the smallest uncompleted issue whose prerequisites are met. Start at **#1** unless fresh verified progress says otherwise.
4. Use a feature branch named `core/CXX-description` where possible; avoid overwriting user changes.
5. Add implementation + tests. Record exact executed validation commands and outputs in issue and/or commit.
6. Close an issue **only after** its checkboxes pass. Update this file with link to commit/PR and explicit next issue.
7. If interrupted, leave an issue comment containing: files changed, current branch/commit, validations performed, remaining steps and blockers.
8. Never claim background execution. Work resumes only when a user starts another authorized session.

## Frozen scope decisions

- Core remains lightweight, with Laravel 12, install/auth/permissions, Control Studio, plugin lifecycle/hooks, basic recovery boundaries.
- SSL God, Email, File Manager, Backup and other site functionality are **separate optional plugins** (not hard-coded Core).
- GitHub stays private for now; plugin development and central update/release server are later stages.
- Secrets, production backups and private keys must never go into source control.

## Handoff template

```md
Date/time & timezone:
Active issue:
Branch / latest commit:
Files actually changed:
What was verified (exact commands):
What failed / needs validation:
What is NOT done:
Next smallest action:
```
