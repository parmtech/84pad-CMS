# 84Pad CMS — Project Resume Record

**Last updated:** 2026-10-27 (Australia/Sydney)

**Current state:** Core implementation is active. Basic page drafting and publishing is implemented on `core/C23-basic-pages`; current code passes the PHP 8.3/8.4, MySQL, and staging-package workflows. PR #27 remains a draft, unreviewed, and unmerged.

**Target repository:** [parmtech/84pad-CMS](https://github.com/parmtech/84pad-CMS) (public)

**Blueprint:** [docs/CORE-BLUEPRINT.md](CORE-BLUEPRINT.md)
**Documentation review:** PR #26 was closed unmerged as superseded; its blueprint and resume record are included and updated in PR #27.

**Current feature review:** [Core basic pages draft PR #27](https://github.com/parmtech/84pad-CMS/pull/27) targets `core/C01-laravel-foundation`; draft and not merged.

## User's current direction

- Build the required 84Pad Core first: a modern, self-hosted CMS foundation with a clear extension model.
- Builder, separate plugins, theme collections, and release-server work come later.
- Keep all project work and the durable resume record in this public GitHub repository.
- The saved blueprint remains the scope reference. The user has now asked to start implementation again.

## Verified repository state at this checkpoint

- The public `main` branch still contains only the README and the two original project documents; no Core implementation has been merged into `main`.
- Several Core implementation branches and draft PRs already exist. Their names and contents are not proof that their features are complete. Review their current state before choosing a base or continuing work.
- Draft PRs observed during this checkpoint:
  - **C01 Laravel Core Foundation** targets `main`.
  - **C20 host preflight hardening** targets the host setup wizard branch.
  - **84Pad Backup v0.1 and extension bridge** targets the Core foundation branch; this is later-stage work under the current direction.
- The C22 public-HTML-upload branch contains substantial Laravel Core source, but it is not merged into `main`. Compare it with the other Core branches before choosing the implementation base.
- No production-ready Core release or live catalog response was verified. The public catalog endpoint returned a staging first-run page, not a release catalog.

## Research and decisions captured

- Relevant saved ChatGPT project conversations reviewed: **84pad Core**, **Installer Wizard Setup**, **84pad website**, **84pad Builder**, **84pad backup**, and **Parmtech Website Current**.
- Current public references reviewed: [84pad.com](https://84pad.com/) and [84pad.com/guides](https://84pad.com/guides/).
- Core and the central release server are separate. Core is the customer-installed CMS; the release server distributes versioned packages and remains deferred.
- Core setup targets PHP 8.3/8.4, MySQL/MariaDB, HTTPS, and required PHP extensions. cPanel-assisted database provisioning is optional; manual database setup is the fallback.
- Use secure one-time owner setup and never include a hard-coded password, API token, private key, or production data.
- Source descriptions disagree about whether Builder, tools, and themes ship inside Core or as separate extensions. Follow the user's latest Core-first direction; treat website feature counts as goals until verified in the actual release.
- The website marks its guided installer as planned and its current PHP Core preview as not production-approved. Backup claims are limited to page/catalog snapshots, not full server or media backups.

## Completed blueprint checkpoint

- Added [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md), covering Core responsibilities, installation, identity, Control Studio, basic pages, extension contracts, migrations, update/recovery boundaries, security, deferred scope, and open decisions.
- Updated this checkpoint to replace stale references to the older private repository and record the current public-repository state.
- Saved the docs-only change on a review branch and opened PR #26. It was later closed unmerged as superseded by the blueprint and updated resume record in PR #27.
- At the blueprint-only checkpoint, no source code, release package, live website, or hosting account was changed.
- No tests were added or run; this task only wrote documentation.

## Current implementation slice — basic pages

- Started from the existing `core/C01-laravel-foundation` draft after checking its current state; did not create a second competing Core foundation.
- Added a Core pages table, page model, owner/admin-only Control Studio routes, draft create/edit, private draft preview, publish/unpublish, and a public page route at `/p/{slug}`.
- Public content is rendered as escaped plain text; arbitrary HTML/scripts are not executed. Drafts are only available in the authenticated admin editor.
- Added a Pages entry to the Control Studio navigation.
- Aligned Composer's PHP constraint to `^8.3`, matching the PHP 8.3/8.4 CI and the stated Core hosting baseline.
- The owner selected GPL-3.0-or-later. Official license text, SPDX metadata, and the README notice are on this branch; they are not yet merged to `main`.
- Current implementation revision [bfe88dc](https://github.com/parmtech/84pad-CMS/commit/bfe88dcd597dc2c04acd573e9dcb4cc5e2b62339) passed [Core CI #40422758337](https://github.com/parmtech/84pad-CMS/actions/runs/40422758337) on PHP 8.3 and 8.4, [MySQL Compatibility #40422758481](https://github.com/parmtech/84pad-CMS/actions/runs/40422758481), and [Stage Distribution #40422758272](https://github.com/parmtech/84pad-CMS/actions/runs/40422758272).
- CI ran the existing test suite and syntax/bootstrap/migration checks; no page-specific tests were added, so the new page routes and publishing flow still need focused verification. Browser/accessibility QA and maintainer review are also outstanding. PHP CLI is unavailable locally.
- Outstanding for this slice: focused page-feature verification, maintainer review, and later browser/shared-host acceptance.

## Pending work

1. Complete focused verification and review of the basic pages slice on `core/C23-basic-pages` before merging it into C01.
2. Recheck the latest state of `main`, all Core branches, open PRs, and CI; do not rely on this snapshot after it becomes stale.
3. Resolve Core-versus-bundled-product claims against the blueprint and update public copy only when the user asks.
4. Continue Core implementation in dependency order using the blueprint. Do not resume plugin, theme, release-server, or live-site work unless the user expands scope.
5. Record actual changes, verification, blockers, and the next action here after each work slice.

## Resume procedure for any AI or chat

1. Read this file and [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md).
2. Inspect the current public repository, branch and PR states, and relevant source before editing.
3. Treat planned features, branch contents, draft PRs, and website claims as unverified until checked.
4. Follow the user's latest scope and update this record with what was actually changed and verified.

