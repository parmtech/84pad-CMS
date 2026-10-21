# 84PAD Core — Resume Checkpoint

> **Source of truth:** GitHub issue states + this file. Do not infer completion from chat messages.
> Last updated: 2026-10-21 (Australia/Sydney).
> **Current phase:** Core 0.1 — foundation.
> **Next issue:** [#2 C02 — Configuration and secret hygiene](https://github.com/manbir6-ux/84pad-core/issues/2).
> **Runnable:** CI verified on PHP 8.3 and 8.4; not deployed to hosting or approved for production.
> **Environment test status:** GitHub Actions [run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743): PHP 8.3 and PHP 8.4 jobs passed Composer install, artisan bootstrap/routes, SQLite migrations, PHPUnit and PHP syntax checks. Local container still lacks Composer and required PHP extensions.

## Work checklist

| ID | Task | State | Proof |
|---|---|---|---|
| [C01](https://github.com/manbir6-ux/84pad-core/issues/1) | Laravel 12 scaffold | **CI verified (PHP 8.3 & 8.4)** | [Run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743); still unmerged and not production approved |
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

### 2026-10-22
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
