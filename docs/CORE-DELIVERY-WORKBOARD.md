# 84PAD Core 0.1 — Delivery Workboard

Status: active development; NOT production-ready.
Owner direction (2026-11-22): finish Core before optional plugins. Basic Pages and published homepage belong in Core. Advanced visual Builder, Backup, SSL, Email, File Manager, SEO and AI remain optional plugins.

## Source of truth
- Preserve existing Laravel Core code and branch history; do not start a replacement project.
- Follow docs/CORE-BLUEPRINT.md and docs/PROGRESS.md.
- Current integration candidate: core/C28-pages-tests; review dependency chain C01 -> C23 -> C24 -> C25 -> C26 -> C27 -> C28, plus C20 installer work independently.
- Do not merge experimental Backup plugin bridge (#24) into Core without security and contract review.

## Execution gates
- [ ] Audit latest branch and all open Core PR diffs, test results and dependency order.
- [ ] Resolve Core installer hardening (#25), verify PHP 8.3/8.4 and MySQL CI, and test clean installation on disposable cPanel.
- [ ] Verify Core authentication, owner roles, permissions, Control Studio and site settings.
- [ ] Complete Basic Pages (create/edit/draft/private preview/publish/unpublish), published homepage, URL and content security.
- [ ] Add focused homepage, site-identity and plugin manifest/capability tests.
- [ ] Verify versioned plugin manifest, discovery, activation/deactivation, hooks, permission boundaries and migrations; enforce deny-by-default.
- [ ] Test update/source recovery failure modes; never present source-only restore as full-site rollback.
- [ ] Verify clean install, repeat-install lockout, browser/mobile/keyboard/accessibility, security, staging packaging.
- [ ] Review and merge safe Core PRs in dependency order only after checks; tag Core 0.1 release candidate only when acceptance gates pass.

## Current evidence
- 2026-11-16 Core CI and MySQL compatibility passed on C28 pages-tests branch at f13bb37.
- Open PRs are drafts; main branch has documentation only. Passing branch CI does not establish a released Core.
- C20 installer PR has previously recorded CI failures; recheck latest status before any merge.
- Production deployment is not authorized by this workboard.

## Next actionable task
Review C20 installer branch and C28 integration candidate against Core blueprint, identify precise blockers and fix them on isolated branches; rerun CI before merging.
