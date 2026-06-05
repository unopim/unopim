# Installer: Optional Packages + Cloud Hosting Banner

**Date:** 2026-06-05
**Status:** Approved (design)
**Scope:** `packages/Webkul/Installer/src/Console/Commands/Installer.php` (the `unopim:install` command)

## Goal

During `php artisan unopim:install`, let the user opt into installing official open-source
add-on packages (checkbox multiselect), and always show a promotional banner at the end of a
successful install pointing to UnoPim cloud hosting.

## Packages offered

| Checkbox label                  | Composer package             | Setup command (artisan)   |
| ------------------------------- | ---------------------------- | ------------------------- |
| Digital Asset Management (DAM)  | `unopim/dam`                 | `dam-package:install`     |
| Shopify Connector               | `unopim/shopify-connector`   | `shopify-package:install` |
| Bagisto Connector               | `unopim/bagisto-connector`   | `bagisto-package:install` |

Notes:
- Each package auto-registers its provider via composer `extra.unopim.laravel.providers`
  (package discovery), so no manual provider wiring is needed.
- Shopify additionally runs `vendor:publish --tag=config --force` and `shopify-package:install`
  via its own composer `post-autoload-dump` script. Re-running `shopify-package:install` from
  the installer is idempotent and acts as a safety net.
- DAM and Bagisto have no composer scripts; their `*-package:install` command runs their
  migrations/setup.

## Why install extras AFTER core, via a spawned subprocess

`composer require` executed mid-run cannot boot the newly required package's service providers
into the **already-running** `unopim:install` process — providers boot once at framework
bootstrap, before `handle()` runs. Consequently a same-process `migrate:fresh` (or
`$this->call('<pkg>-package:install')`) would not see the new package's migrations or command.

Therefore: core install completes first (unchanged). Then for each selected package we
`composer require` it, then spawn a **fresh** `php artisan <pkg>-package:install` subprocess.
The fresh process reads the regenerated `bootstrap/cache/packages.php`, discovers the new
provider, and runs the package's own migrations/setup correctly. This mirrors what a user does
manually when adding a package to an existing install.

Rejected alternatives:
- **require-before-core-migrate, single process** — new providers never load in-process; the
  package's migrations are silently skipped. Broken.
- **composer require only, rely on post-autoload scripts** — only Shopify has such scripts;
  DAM and Bagisto would be left un-set-up. Incomplete.

## Changes to `Installer::handle()`

1. **New property** `$optionalPackages` — the map above:
   `key => ['composer' => '...', 'label' => '...', 'install' => '<pkg>-package:install']`.
   Keys: `dam`, `shopify`, `bagisto`.

2. **New CLI option** `--with-packages=` (CSV, supports `*` array form), e.g.
   `--with-packages=dam,shopify`. Enables non-interactive / CI installs. Unknown keys are
   warned and skipped.

3. **Selection step** (prompt phase): if `--with-packages` not provided and the run is
   interactive, ask a `multiselect`:
   "Select optional packages to install (press space to toggle, enter to confirm)" with the
   three labels. Default: none selected. The resolved selection is stored on the command.

4. **New install step** in `handle()`, placed **after** the demo-data block and **before**
   `markInstalled()`: `installOptionalPackages(array $keys)`:
   - For each selected key: run `composer require <composer-package>` via Symfony `Process`
     (`cwd = base_path()`, generous timeout, output streamed to console).
   - On success, spawn `php artisan <install-command>` via `Process` (fresh boot).
   - On composer/network/exit failure for a package: print a warning plus the manual commands
     (`composer require <pkg>` and `php artisan <install-command>`), then continue. A failed
     optional package MUST NOT abort the core install or fail the command.

5. **Cloud hosting banner**: always rendered at the very end of a successful install via
   `renderCloudHostingBanner()` — a boxed promotional message including the URL
   `https://unopim.com/cloud-hosting/`.

## New units (keep `handle()` thin, both testable)

- `installOptionalPackages(array $keys): void` — orchestrates require + setup per package.
- `resolveSelectedPackages(): array` — merges `--with-packages` option and/or the multiselect
  answer into a validated list of known keys (drops/warns unknown keys). Pure enough to unit
  test without spawning processes.
- `renderCloudHostingBanner(): void` — prints the promo box + URL.

The actual `Process` spawning is isolated in `installOptionalPackages` so tests can drive the
key-resolution and banner logic without invoking composer.

## Strings

The installer command is currently 100% hardcoded English (`$this->info('Step: ...')`, etc.).
New console lines follow the same convention — hardcoded English, **not** `trans()` — for
consistency with the surrounding file. (Decision confirmed with user.)

## Error handling

- Composer binary missing or `Process` non-zero exit → caught per package; warn + manual
  instructions; continue.
- Long-running composer download → `Process` timeout set high (e.g. null/disabled or several
  minutes) with streamed output so it doesn't look hung.
- Core install behavior is unchanged when no packages are selected.

## Testing (Pest — Installer suite)

1. `resolveSelectedPackages()` maps `--with-packages=dam,shopify` to the correct composer
   names and install commands; ignores/warns unknown keys.
2. Running the installer with no packages selected performs **no** composer invocation and
   the cloud banner is still printed.
3. Banner output contains `https://unopim.com/cloud-hosting/`.

## Out of scope

- No refactor of the existing core install steps or the duplicate "installed" marker logic in
  `createAdminCredentials()`.
- No new admin-panel UI; this is CLI-only.
- No version pinning UI for the packages (composer resolves latest compatible).
