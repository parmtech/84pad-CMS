# 84Pad CMS — Project Resume Record

**Last updated:** 2026-10-26 (Australia/Sydney)

**Current state:** Core implementation resumed at the user's request. The first slice is basic page drafting and publishing on `core/C23-basic-pages`; unmerged and unverified.

**Target repository:** [parmtech/84pad-CMS](https://github.com/parmtech/84pad-CMS) (public)

**Blueprint:** [docs/CORE-BLUEPRINT.md](CORE-BLUEPRINT.md)
**Documentation review:** [Core blueprint and resume record pull request](https://github.com/parmtech/84pad-CMS/pull/26) (open draft; not merged; blueprint checkpoint will be updated with this implementation slice)

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
- Saved the docs-only change on a review branch and opened the draft PR linked above; it has not been merged.
- No source code, release package, live website, or hosting account was changed.
- No tests were added or run; this task only wrote documentation.

## Current implementation slice — basic pages

- Started from the existing `core/C01-laravel-foundation` draft after checking its current state; did not create a second competing Core foundation.
- Added a Core pages table, page model, owner/admin-only Control Studio routes, draft create/edit, publish/unpublish, and a public page route at `/p/{slug}`.
- Public content is rendered as escaped plain text; arbitrary HTML/scripts are not executed. Drafts are only available in the authenticated admin editor.
- Added a Pages entry to the Control Studio navigation.
- The slice is committed and present on `core/C23-basic-pages`, but CI and review have not yet verified it. PHP CLI is not installed in the current environment, so local PHP syntax checks could not run. Do not claim the feature is verified until CI and review evidence exist.
- Outstanding for this slice: review migration/controller behavior, verify it through repository CI, update this checkpoint with the PR and CI result, and address review findings.

## Pending work

1. Finish and verify the basic pages slice on `core/C23-basic-pages` before starting another feature.
2. Recheck the latest state of `main`, all Core branches, open PRs, and CI; do not rely on this snapshot after it becomes stale.
3. Resolve Core-versus-bundled-product claims against the blueprint and update public copy only when the user asks.
4. Continue Core implementation in dependency order using the blueprint. Do not resume plugin, theme, release-server, or live-site work unless the user expands scope.
5. Record actual changes, verification, blockers, and the next action here after each work slice.

## Resume procedure for any AI or chat

1. Read this file and [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md).
2. Inspect the current public repository, branch and PR states, and relevant source before editing.
3. Treat planned features, branch contents, draft PRs, and website claims as unverified until checked.
4. Follow the user's latest scope and update this record with what was actually changed and verified.

