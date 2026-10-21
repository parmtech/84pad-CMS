# 84PAD Core — Resume Checkpoint

> **Source of truth:** GitHub issue states + this file. Do not infer completion from chat messages.
> Last updated: 2026-10-21 (Australia/Sydney).
> **Current phase:** Core 0.1 — foundation verified on earlier CI commit; installer/auth/plugin preview code added and now requires its own verification.
> **Next issue:** [#3 C03 — Installation prerequisite checks](https://github.com/manbir6-ux/84pad-core/issues/3).
> **Runnable:** Earlier foundation passed CI on PHP 8.3 and 8.4. LATER code commits must pass NEW CI before their functionality can be claimed runnable. Not approved for production.
> **Environment test status:** Earlier run [39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743) passed foundation tests. Latest post-feature CI [39605970221](https://github.com/manbir6-ux/84pad-core/actions/runs/39605970221) was **PENDING at verification**. Local container lacks Composer and required PHP extensions; DO NOT claim later features tested until a completed green run is checked.

## Work checklist

| ID | Task | State | Proof |
|---|---|---|---|
| [C01](https://github.com/manbir6-ux/84pad-core/issues/1) | Laravel 12 scaffold | **CI verified (PHP 8.3 & 8.4)** | [Run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743); still unmerged and not production approved |
| [C02](https://github.com/manbir6-ux/84pad-core/issues/2) | Config and secret hygiene | **CI verified; issue closed** | [Run 39605702869](https://github.com/manbir6-ux/84pad-core/actions/runs/39605702869); nested secret ignores + filename guard |
| [C03](https://github.com/manbir6-ux/84pad-core/issues/3) | Installer preflight checks | **In progress** | Host-preflight command committed; acceptance/safety tests pending |
| [C04](https://github.com/manbir6-ux/84pad-core/issues/4) | One-time owner installer | **Implementation preview** | CLI owner install code exists; comprehensive recovery/race testing pending |
| [C05](https://github.com/manbir6-ux/84pad-core/issues/5) | Authentication | **Implementation preview** | Owner login/logout guarded routes and feature tests; security review pending |
| [C06](https://github.com/manbir6-ux/84pad-core/issues/6) | Authorization | Partial | Owner-only controls; full RBAC not implemented |
| [C07](https://github.com/manbir6-ux/84pad-core/issues/7) | Control Studio dashboard | **Implementation preview** | Initial owner dashboard view; gaming UI polish and browser testing pending |
| [C08](https://github.com/manbir6-ux/84pad-core/issues/8) | Plugin manifest | Partial source added | JSON manifest scanner; no signatures |
| [C09](https://github.com/manbir6-ux/84pad-core/issues/9) | Plugin discovery | Partial source added | Metadata-only discovery; unverified |
| [C10](https://github.com/manbir6-ux/84pad-core/issues/10) | Plugin activation/deactivation | Partial source added | DB activation **markers only**; no untrusted plugin PHP execution |
| [C11](https://github.com/manbir6-ux/84pad-core/issues/11) | Hooks API | Draft code added | HookBus; not connected to plugin execution |
| [C12](https://github.com/manbir6-ux/84pad-core/issues/12) | Demo plugin | Manifest only | Demo is not executable |
| [C13](https://github.com/manbir6-ux/84pad-core/issues/13) | Database/plugin migration strategy | Partial schema | Users/cache/jobs/install/plugin tables; rollback strategy pending |
| [C14](https://github.com/manbir6-ux/84pad-core/issues/14) | Update/recovery interfaces | Not started | none |
| [C15](https://github.com/manbir6-ux/84pad-core/issues/15) | QA and handoff | Not started | none |

## Work log

### Latest development checkpoint
- Feature branch: [core/C01-laravel-foundation](https://github.com/manbir6-ux/84pad-core/tree/core/C01-laravel-foundation) (DO NOT merge as production ready).
- Committed Laravel config and standard migration structure: [c2f52fc](https://github.com/manbir6-ux/84pad-core/commit/c2f52fc626e1dacecc6f860259449c004b5c4d8e).
- Added CLI installer, owner login and Control Studio development previews: [b660356](https://github.com/manbir6-ux/84pad-core/commit/b660356a15c6212fd16cbbfd4edeaed4158fc4a8).
- Fixed atomic installation sentinel (nullable foreign key) and wired auth routes: [6b99982](https://github.com/manbir6-ux/84pad-core/commit/6b9998246fe8bff20b116925ec7ff1e5eed1fe94).
- Added metadata-only plugin discovery, registry and hooks: [f6d51b3](https://github.com/manbir6-ux/84pad-core/commit/f6d51b35ef84ecd17695adc0239fa0748dd37672).
- Added installation preflight command: [ccbf991](https://github.com/manbir6-ux/84pad-core/commit/ccbf99170d55621fc7a3fa0e6ffeba58a4dcb5c0).
- Added owner-only plugin state UI and feature tests: [219ce12](https://github.com/manbir6-ux/84pad-core/commit/219ce128d075866f956281fd65561531c1acca4f).
- CI on latest feature code was **pending** when inspected; no permission to declare C03–C15 complete. Core 0.1 is **unfinished**.
- **Next action:** check most recent CI logs, fix any red tests, validate `84pad:doctor`, CLI installation once-only, login security and plugin status tests. Then assess issue completion individually.

## Archived work log

### 2026-10-22
- Completed C02 initial credential hygiene: strengthened `.gitignore`, added `scripts/check_tracked_secrets.py`, and enforced it in Core CI. [Run 39605702869](https://github.com/manbir6-ux/84pad-core/actions/runs/39605702869) passed on PHP 8.3 and PHP 8.4. Issue #2 closed; path-name scanning is not comprehensive secret-content scanning.
- C03 read-only preflight command has landed in a parallel Core commit but still needs its own acceptance tests. C04/C05/C07 have initial previews, not production releases.
- Added `.github/workflows/core-ci.yml` on `core/C01-laravel-foundation` ([commit 632df90](https://github.com/manbir6-ux/84pad-core/commit/632df90b5cf87fef53ff9ca103a6f4cdfde4c895)).
- [GitHub Actions run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743) **PASSED** on PHP 8.3 and 8.4: Composer validation/install, environment setup, Laravel bootstrap/routes, SQLite migrations, PHPUnit tests, and PHP lint.
- Foundation now has model/config/migration/owner CLI/auth/control-studio **development previews**; installed on CI runners only. No cPanel production or UI browser acceptance tests done.
- Opened [draft PR #17](https://github.com/manbir6-ux/84pad-core/pull/17) for review; do NOT merge as a production release. Next focus: C02 config/secrets; C03+ require separate acceptance and security tests.


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
