# 84Pad CMS — Project Resume Record

**Last updated:** 2026-10-27 (Australia/Sydney)

**Current state:** Core implementation is active on review branches. PRs #27–#31 are open drafts with no submitted reviews and are not merged. Pages PR #27 passes PHP 8.3/8.4, MySQL, and staging-package workflows; site identity PR #28 passes PHP 8.3/8.4 and staging-package CI; homepage PR #29 passes PHP 8.3/8.4 with no staging-package result recorded; plugin capability PR #30 passes PHP 8.3/8.4 with no staging-package result recorded; source-only restore PR #31 passes PHP 8.3/8.4 on head `8495f11` ([run #40426343362](https://github.com/parmtech/84pad-CMS/actions/runs/40426343362)). Focused restore acceptance and maintainer review remain outstanding.

**Target repository:** [parmtech/84pad-CMS](https://github.com/parmtech/84pad-CMS) (public)

**Blueprint:** [docs/CORE-BLUEPRINT.md](CORE-BLUEPRINT.md)
**Documentation review:** PR #26 was closed unmerged as superseded; its blueprint and resume record are included and updated in PR #27.

**Current feature review:** [Core basic pages draft PR #27](https://github.com/parmtech/84pad-CMS/pull/27) targets `core/C01-laravel-foundation`; draft and not merged.

**Current site settings work:** [Core site identity draft PR #28](https://github.com/parmtech/84pad-CMS/pull/28) targets `core/C23-basic-pages`; draft and not merged.

**Current homepage work:** [Core homepage draft PR #29](https://github.com/parmtech/84pad-CMS/pull/29) targets `core/C24-site-identity`; draft and not merged.

**Current plugin contract work:** [Draft PR #30](https://github.com/parmtech/84pad-CMS/pull/30) targets `core/C25-homepage`. It adds declared cPanel gateway capabilities, grant invalidation when declarations change, a manifest size limit, and owner-visible capability requests. PHP 8.3/8.4 CI passes on implementation revision `f6ec3cc`; no staging-package result is recorded.

## User's current direction

- Build the required 84Pad Core first: a modern, self-hosted CMS foundation with a clear extension model.
- Builder, separate plugins, theme collections, and release-server work come later.
- Keep all project work and the durable resume record in this public GitHub repository.
- The saved blueprint remains the scope reference. The user has now asked to start implementation again.

## Verified repository state at this checkpoint

- The public `main` branch still contains only the README and the two original project documents; no Core implementation has been merged into `main`.
- On the latest check, `main` README still says the Laravel scaffold is not verified runnable; `LICENSE` and `composer.json` are absent there. The selected GPL-3.0-or-later notice, license file and Composer metadata exist on the open Core development branches, but are not yet part of `main`.
- Open draft PRs #27–#31 were checked for submitted reviews; none had a review at this checkpoint. Their green CI checks do not constitute maintainer approval or a merge.
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

## Current implementation slice — site identity

- Added owner-only controls for site title, canonical URL, language and time zone using the existing private Core settings store.
- Public published-page views use these settings for the page language, site title, canonical link, and displayed publication time. Private previews remain non-indexable and uncached.
- Canonical URLs must be HTTPS outside local/testing environments and cannot contain credentials, query parameters, or fragments.
- The implementation revision [f05910a](https://github.com/parmtech/84pad-CMS/commit/f05910a8fa7d940734b7e3758eec30dd4f8ce578) passed [Core CI #40423368199](https://github.com/parmtech/84pad-CMS/actions/runs/40423368199) on PHP 8.3 and 8.4 and [Stage Distribution #40423368180](https://github.com/parmtech/84pad-CMS/actions/runs/40423368180).
- CI covered the existing suite, migration/bootstrap checks and PHP syntax; no site-settings-specific tests were added. Browser/accessibility review and maintainer review remain outstanding. PHP CLI is unavailable locally.
- Next: review canonical URL validation, add focused verification when authorized, and complete browser acceptance.

## Current implementation slice — published homepage selection

- In progress on `core/C25-homepage`, based on the site identity branch.
- Adds an owner setting to select a published page as the public `/` page. The configured homepage remains the ordinary page at `/p/{slug}` as well, with its canonical URL pointing to `/` when rendered at the root.
- If no page is selected, or the selected page is unpublished, `/` returns to the default Core welcome page. The same fallback remains available before installation and if the database is unavailable. The setting is stored in Core settings and does not require a schema migration.
- Implementation revision [90cf068](https://github.com/parmtech/84pad-CMS/commit/90cf068ecd98e9fe80af6f67b93cb65fbeee6453) passed [PHP 8.3/8.4 CI #40424115625](https://github.com/parmtech/84pad-CMS/actions/runs/40424115625). No homepage-specific tests were added, and no staging-package workflow result is recorded. Browser and accessibility acceptance remain outstanding.

## Pending work

1. Verify and review owner-managed site identity on `core/C24-site-identity`.
2. Complete focused verification and maintainer review of Pages PR #27 before merging it into C01.
3. Review the homepage PR and arrange staging-package, focused, and browser verification before merging; obtain maintainer review.
4. Complete focused verification and maintainer review of pages, site settings, and homepage behavior before merging their dependent changes.
5. Inspect GitHub CI and focused recovery review for C27 PR #31, then update this record.
6. Recheck the latest state of `main`, all Core branches, open PRs, and CI; do not rely on this snapshot after it becomes stale.
7. Resolve Core-versus-bundled-product claims against the blueprint and update public copy only when the user asks.
8. Continue Core implementation in dependency order using the blueprint. Do not resume separate plugins, theme collections, release-server, or live-site work unless the user expands scope.
9. Record actual changes, verification, blockers, and the next action here after each work slice.

## Current implementation slice — manifest-declared cPanel capabilities

- In progress on `core/C26-manifest-capabilities`, based on homepage branch `core/C25-homepage`.
- Adds optional `cpanel_capabilities` to plugin manifests. Missing means no cPanel access; malformed, duplicate, or unknown capability declarations are rejected.
- Owner settings can grant only requested capabilities. Grants are bound to a stable hash of the plugin ID and declared capability set; changing that set invalidates old grants until the Owner reviews and grants again.
- Control Studio shows the plugin's requested capabilities and compatibility before activation. Settings explains that these grants govern only Core's fixed cPanel gateway; PHP plugins retain full host privileges.
- A migration stores the grant scope hash. Existing grants without that hash are ineffective until explicitly granted again.
- Implementation revision [f6ec3cc](https://github.com/parmtech/84pad-CMS/commit/f6ec3cc7eab55bb32b8d7a0520c8934869816520) passed [PHP 8.3/8.4 CI #40424902320](https://github.com/parmtech/84pad-CMS/actions/runs/40424902320). PHP 8.3 ran the existing suite: 83 passed, 1 skipped (344 assertions). No new manifest-scope tests were added, and no staging-package result is recorded. Browser and maintainer review remain outstanding.

## Current implementation slice — source-only Core restore

- In draft PR #31 on `core/C27-source-restore`, based on `core/C26-manifest-capabilities` (PR #30).
- Extends the private Core source snapshot command with an operator-confirmed `--restore` operation requiring an independently recorded SHA-256 digest. It creates and verifies a pre-restore source snapshot, enters Laravel maintenance mode, stages and verifies the selected files, then replaces them. Failed replacement triggers automatic source recovery; if recovery is incomplete, the command leaves the application in maintenance mode and reports the recovery snapshot location and digest.
- Restore is explicitly limited: it replaces only files listed in the selected source snapshot; later-added files remain. It does not restore databases, installed plugins, uploads, dependencies, hosting settings, or `.env`. This is not the signed Core updater or a full-site rollback.
- Tightened snapshot path validation and updated `CORE-UPDATES.md`, `CORE-BLUEPRINT.md`, and the README to document the behavior and limitations. GPL-3.0-or-later was selected by the owner and is already recorded in `LICENSE` and Composer metadata on the base branch.
- PHP 8.3/8.4 CI passed on revision [8bbadc3](https://github.com/parmtech/84pad-CMS/commit/8bbadc3e282bedcece6d3c287820d83c7ae96d1d): [workflow run #40426164963](https://github.com/parmtech/84pad-CMS/actions/runs/40426164963). GitHub reported only Node 20 and Ubuntu runner deprecation notices. No tests were added or run locally. No staging-package result is recorded. Focused restore acceptance and maintainer review remain outstanding.

## Resume procedure for any AI or chat

1. Read this file and [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md).
2. Inspect the current public repository, branch and PR states, and relevant source before editing.
3. Treat planned features, branch contents, draft PRs, and website claims as unverified until checked.
4. Follow the user's latest scope and update this record with what was actually changed and verified.



