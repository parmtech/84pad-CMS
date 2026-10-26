# 84Pad CMS Core Blueprint

**Document status:** Product and architecture blueprint; not evidence that these features are implemented.
**Current instruction:** Build the required Core first. Pause implementation after this blueprint until the user resumes work.
**Target repository:** [parmtech/84pad-CMS](https://github.com/parmtech/84pad-CMS)

## 1. Product definition

84Pad CMS is intended to be a modern, self-hosted content management system with a modular extension model. The required **Core** is the installed runtime: it owns setup, identity and access, site settings, basic content publishing, and the interfaces that later extensions use.

Core is not itself an optional plugin. A Core ZIP is installed on a customer-controlled hosting account. Plugins and themes are extensions that depend on Core and should be installable separately when their release packages are ready.

The central release service is a separate product component. It distributes versioned Core ZIPs and signed extension packages; it is not part of a customer’s CMS runtime.

## 2. Product-source alignment

This blueprint uses the user's latest direction: **Core first; Builder, other plugins, themes, and release-service work later.**

The sources currently describe different packaging boundaries:

- The GitHub README defines a lightweight Core with installation, authentication, Control Studio, plugin lifecycle, hooks, migrations, and update/recovery interfaces. It describes pages, Builder, themes, SSL, backups, and other tools as optional extensions.
- The public website describes a fuller bundle containing Core, Studio, Builder, first-party tools, and built-in themes. It advertises seven tools, eight themes, and 96+ starter designs.
- The 84Pad project conversations describe Core ZIPs separately from signed `.pad` plugin/theme packages and repeatedly treat Builder and Backup as later plugin work.

For the first Core implementation, the user’s latest Core-first direction takes precedence. Website feature counts and bundled-product statements are product goals, not proof that those features exist. Before any public release, align the website, package contents, and repository documentation with verified implementation.

## 3. Hosting and installation

### Supported baseline

The current public setup guidance targets PHP 8.3 or 8.4, MySQL or MariaDB, HTTPS, and the required PHP extensions, including ZIP and Sodium. The repository proposes Laravel 12 and Composer. These are planned compatibility targets and must be verified against the final dependency set and the actual shared-hosting deployment model before a release is called supported.

The installer must show which requirement is missing and how the site owner can resolve it. It must not silently weaken requirements.

### First-run setup flow

1. **Preflight:** Check the PHP version, required extensions, writable private storage, HTTPS status, database reachability, and safe application paths. Show clear pass/fail results.
2. **Choose database setup:** Offer manual MySQL/MariaDB details as the dependable path. Offer cPanel-assisted creation only when the host supports it and the owner supplies a scoped API token.
3. **Protect credentials:** Send cPanel requests over HTTPS, validate the destination host, redact tokens from logs, and avoid retaining the token after provisioning unless the owner explicitly chooses a documented secure connection feature.
4. **Create the site:** Generate application secrets, write private configuration outside the public document root, run Core migrations, create the first owner account, and set initial site identity.
5. **Lock setup:** Record that installation is complete, prevent setup routes from creating a second owner, and require a deliberate, authenticated recovery path for repair.
6. **Verify and hand off:** Confirm the owner can sign in, show the configured public URL, and provide a short post-install checklist. The installer must never claim that an email reset works unless mail transport has been configured and verified.

A simple guided installer is a Core feature target. The current website marks its guided installer as planned and says the present PHP Core preview is not production-approved.

## 4. Core features and behavior

### 4.1 Site runtime and configuration

Core loads configuration, routes requests, connects to the supported database, and serves the public site and private Control Studio. Site settings include the canonical site URL, title, locale/time zone, and safe operational settings. Secrets and runtime data stay outside public files and out of source control.

### 4.2 Owner identity, authentication, and access

The installer creates the initial owner account. Passwords are stored using the framework’s secure password hashing. Sign-in uses CSRF protection, secure session handling, rate limiting, and safe error messages.

The first release needs clear owner permissions. The authorization layer should support additional roles and capabilities without requiring a redesign. Every administrative route and extension action must check permission on the server, not only hide a button in the interface.

Password change is available to an authenticated owner. Password reset is only enabled when outbound mail is configured; reset links must be short-lived, single-use, and protected against account enumeration. Never ship a shared default password or hard-code credentials.

### 4.3 Control Studio

Control Studio is the responsive, accessible admin workspace for Core. Its first Core scope is:

- A clear overview of installation health, site status, and pending setup.
- Site identity and Core settings.
- Owner profile, password change, and account/session controls.
- Basic page management and publishing.
- Extension inventory and Core compatibility status.
- Update and recovery status when those services are configured.

Navigation and controls must work by keyboard, show visible focus, use readable contrast, and adapt to narrow screens. Advanced visual page design belongs to a later Builder release.

### 4.4 Basic pages and publishing

A usable CMS Core needs a small content path before a visual Builder exists:

- Create and edit a page with a title, stable URL slug, and body.
- Save as draft, preview privately, publish, unpublish, and update.
- Render published pages through a safe public template.
- Keep drafts and administrative details private.
- Validate slugs and prevent unsafe HTML/script execution in ordinary content.
- Provide a minimal default presentation so a fresh installation can publish a working site.

Page revisions, rich block composition, large media libraries, SEO automation, and product catalogues are later capabilities unless separately approved as Core requirements.

### 4.5 Plugin and theme extension contracts

Core owns the extension boundary, not the future features themselves.

A plugin contract should define a manifest, package identity, version, supported Core range, declared capabilities, entry points, and schema migrations. The package manager should:

1. Inspect package format and metadata before installation.
2. Reject path traversal, malformed archives, incompatible versions, and invalid signatures where signing is enabled.
3. Place a new package in a non-active staging area.
4. Show the owner its identity, requested capabilities, and compatibility before activation.
5. Activate/deactivate through a recorded lifecycle, with a recoverable state if a migration or activation fails.
6. Retain a safe uninstall boundary that does not silently delete user content or unrelated data.

Hooks/events and documented service interfaces let extensions integrate without patching Core files. Extension code is not trusted merely because it is present in the plugins folder. Permissions, integrity checks, version compatibility, and clear failure reporting are required.

Themes need a stable rendering and design-token interface, but theme packs and a theme marketplace are deferred. Core should provide a minimal default presentation and a contract for later themes.

The saved project and website refer to signed `.pad` extension packages. Their exact archive layout, signature format, trusted-key rotation process, and recovery behavior must be decided and documented before real packages are accepted.

### 4.6 Database changes

Core migrations are versioned, repeatable only where safe, and tracked so an interrupted migration can be diagnosed. MySQL and MariaDB are the stated target databases. Extension migrations run through Core’s lifecycle and must report failures clearly.

Schema changes must preserve content or stop safely. Destructive data removal requires an explicit, recoverable operation. Upgrade and restore behavior must be designed before declaring an update path production-ready.

### 4.7 Updates and recovery boundary

Core should expose a safe update interface that can:

- Read release metadata from a configured service.
- Verify package identity, version, integrity, and signature before applying it.
- Check compatibility and storage requirements.
- Create a recoverable pre-update snapshot or clearly block when recovery is unavailable.
- Apply updates in a staged operation and report success/failure.
- Restore the previous version or provide a documented recovery procedure after a failed update.

The release server itself remains separate and is deferred. The public catalog URL currently returns a staging first-run page rather than a release catalog response; do not treat that endpoint as a working production update service.

## 5. Security and data rules

- Never commit environment files, credentials, private signing keys, owner passwords, production data, or backups.
- Keep private application files, logs, temporary uploads, and database/configuration data outside the public document root where hosting permits.
- Use least privilege for database and hosting API credentials.
- Validate input server-side, escape output, protect state-changing requests with CSRF controls, and rate-limit authentication and sensitive operations.
- Require HTTPS for setup, sign-in, cPanel API use, password resets, and update downloads.
- Do not execute uploaded packages during inspection. Validate and stage them before activation.
- Log security-relevant events without logging secrets.
- Treat a successful installation as a one-time state transition. Recovery must be authenticated and auditable.

## 6. Explicitly deferred from the first Core milestone

These are later products or extensions, not reasons to hard-code feature implementations into Core now:

- Professional drag-and-drop Builder, starter-design library, and advanced responsive editor.
- SSL issuance/renewal tools, including Certbot and cPanel AutoSSL.
- Scheduled backups, import/export, and restore tooling. The current website describes page/catalog snapshots only; it does not describe a full server or media backup.
- Email management, file management, SEO/AI tools, insights, forms, and storefront functions.
- Theme packs and a theme marketplace.
- Central release server, public package catalog, and production release publishing.

Core may define the interfaces these components will need, but must not advertise deferred capabilities as shipped. The website currently says Storefront does not process carts, checkout, tax, or payments.

## 7. Implementation order and completion evidence

Use small, reviewable slices in dependency order. The repository’s existing C01–C15 checklist is a useful sequence:

1. Laravel foundation and dependency/runtime baseline.
2. Configuration and secret hygiene.
3. Installer preflight and one-time owner setup.
4. Authentication and authorization.
5. Control Studio shell and site settings.
6. Basic pages and public rendering.
7. Plugin manifest, discovery, lifecycle, hooks, and one example extension contract.
8. Database and extension migration behavior.
9. Update/recovery interfaces.
10. Clean-install, compatibility, security, and handoff review.

A feature is **planned** until code exists; **implemented, unverified** until the required checks have run; and **verified** only when evidence is recorded. A preview package is not a production release.

## 8. Decisions still needed before release

- Confirm the final Core-versus-bundled-product boundary and update the website accordingly.
- Verify Laravel 12 and PHP 8.3/8.4 support against dependencies and target hosting.
- Decide the exact public-directory/private-storage layout for shared hosting.
- Finalize cPanel credential scope and whether credentials are ever retained.
- Finalize mail transport and password reset setup.
- Define the signed `.pad` format, trust roots, key rotation, and revocation.
- Define page/media revision and backup responsibilities.
- Confirm the release catalog/API contract before wiring production updates.

## Sources consulted

- [84Pad CMS public site](https://84pad.com/)
- [84Pad guides](https://84pad.com/guides/)
- [84pad-CMS README](https://github.com/parmtech/84pad-CMS/blob/main/README.md)
- [84pad-CMS progress checkpoint](https://github.com/parmtech/84pad-CMS/blob/main/docs/PROGRESS.md)
- [84pad-CMS development workflow](https://github.com/parmtech/84pad-CMS/blob/main/docs/WORKFLOW.md)
