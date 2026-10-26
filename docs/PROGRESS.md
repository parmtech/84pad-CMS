# 84Pad CMS — Project Resume Record

**Last updated:** 2026-10-26 (Australia/Sydney)  
**Current state:** Paused at the user's request after saving the Core blueprint. Do not resume implementation until the user asks.  
**Target repository:** [parmtech/84pad-CMS](https://github.com/parmtech/84pad-CMS) (public)  
**Blueprint:** [docs/CORE-BLUEPRINT.md](CORE-BLUEPRINT.md)

## User's current direction

- Build the required 84Pad Core first: a modern, self-hosted CMS foundation with a clear extension model.
- Builder, separate plugins, theme collections, and release-server work come later.
- Keep all project work and the durable resume record in this public GitHub repository.
- The immediate task was documentation only. The user asked to save the complete Core blueprint, then pause.

## Verified repository state at this checkpoint

- The public `main` branch still contains only the README and the two project documents; no Core implementation has been merged into `main`.
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

## Completed in this documentation-only pause

- Added [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md), covering Core responsibilities, installation, identity, Control Studio, basic pages, extension contracts, migrations, update/recovery boundaries, security, deferred scope, and open decisions.
- Updated this checkpoint to replace stale references to the older private repository and record the current public-repository state.
- No source code, release package, live website, or hosting account was changed.
- No tests were added or run; this task only wrote documentation.

## Pending for a later, user-authorized session

1. Recheck the latest state of `main`, all Core branches, open PRs, and CI; do not rely on this snapshot after it becomes stale.
2. Resolve Core-versus-bundled-product claims against the blueprint and update public copy only when the user asks.
3. Select the correct Core implementation base after comparing the existing branches and their PRs.
4. Continue Core implementation in dependency order using the blueprint. Do not resume plugin, theme, release-server, or live-site work unless the user expands scope.
5. Record actual changes, verification, blockers, and the next action here after each future work slice.

## Resume procedure for any AI or chat

1. Read this file and [CORE-BLUEPRINT.md](CORE-BLUEPRINT.md).
2. Inspect the current public repository, branch and PR states, and relevant source before editing.
3. Treat planned features, branch contents, draft PRs, and website claims as unverified until checked.
4. Follow the user's latest scope and update this record with what was actually changed and verified.
