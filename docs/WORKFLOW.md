# 84PAD — Stop / Resume Development Workflow

This workflow is designed for multiple short ChatGPT/Codex work sessions without losing track of changes.

## One issue = one small deliverable

Every GitHub issue must document:
- **Goal:** What is changing, not a vague phase name.
- **Acceptance criteria:** Observable checkboxes / negative safety cases.
- **Scope:** Files and boundaries that may be changed.
- **Verification:** Tests or commands to prove completion.
- **Handoff:** Next action when work is interrupted.

Don't create an issue titled "Build the whole CMS"; split a larger task until a new session can understand it immediately.

## The session protocol

**Before coding**
1. Read `README.md` and `docs/PROGRESS.md`.
2. Check repository branch, relevant source paths, most recent commits, and open GitHub issues.
3. Select a single issue by dependency order. Confirm no other in-flight changes would conflict.
4. Explicitly distinguish planned work, written code and tested functionality.

**During coding**
1. Use a branch `core/CXX-short-title` if branch creation is available.
2. Make small, reviewable commits per tested slice.
3. Avoid unrelated changes and never commit `.env`, API keys or production data.
4. Re-run relevant tests for each code change when environment supports it.

**At stopping point**
1. Commit or clearly report any uncommitted work.
2. Comment on active issue with exact file paths, commit link/SHA, test results and next TODO.
3. Update `docs/PROGRESS.md` as the human-readable resume checkpoint.
4. Leave issue open unless all acceptance criteria actually pass; update next issue.
5. Do not mark tasks Done merely because a plan, outline or commit exists.

## Completion gate

An issue is **Done** only when all listed acceptance checks pass and results are recorded.
A feature is **Implemented, untested** when code is committed but tests cannot run.
A feature is **Planned** when no code is committed.
**Blocked** means execution was attempted and a concrete dependency/permission/error stops progress.

Core 0.1 is not production-ready until C15 clean installation, tests, security review and handoff pass.

## Order of work

Foundation: C01 → C02 → C03 → C04 → C05 → C06.
UI: C07 after auth shell.
Plugin contract: C08 → C09 → C10 → C11 → C12; migration work C13 as needed.
Update/recovery: C14 after plugin and migration concepts are stable.
Release acceptance: C15 after all core issues pass.

## Non-core stages (deferred)

After Core: SSL God, Email, File Manager, Backup/restore plugins. A **separate** centrally hosted release manager comes later; no need to create now. Any plugin should be installable without modifying Core.
