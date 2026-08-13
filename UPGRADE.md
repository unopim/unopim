# 🔼 UPGRADE GUIDE

> This guide upgrades an existing UnoPim installation to the current release
> without downtime you did not plan for and without losing data.
>
> Read the [CHANGELOG](CHANGELOG.md) and the
> [release notes](https://github.com/unopim/unopim/releases) before you start.
> This is a major release: it changes the PHP requirement, invalidates existing
> API tokens, and runs migrations that cannot be reversed.

- [Before you start](#before-you-start)
- [How the upgrade works](#how-the-upgrade-works)
- [Track A — release archive](#track-a)
- [Track B — Git checkout](#track-b)
- [Track C — Docker](#track-c)
- [After the upgrade](#after-the-upgrade)
- [Rollback](#rollback)
- [How long will it take?](#how-long)
- [Command reference](#command-reference)
- [Where this is going](#roadmap)

---

<a name="before-you-start"></a>
## ⚠️ Before you start

Each of these needs a decision or an action **before** you book downtime.

### PHP 8.4.1 is required

The release does not run on PHP 8.3. Upgrade PHP on the server first, or the
upgrade will refuse to start.

```bash
php -v
```

### Every API client must re-authenticate

Earlier releases shipped a shared OAuth signing key pair. They are no longer
distributed — each installation now generates its own. Replacing the old pair
invalidates every existing access and refresh token.

Plan for this: after the upgrade, every integration must obtain new tokens.
Integrations are also migrated to dedicated robot users, and their credentials
are revealed once in the admin panel.

This is the last upgrade that invalidates tokens. The key pair now lives in
`storage/app/private/oauth` instead of the application source tree, so it is no
longer destroyed when the code is replaced. On Docker that means the volume
keeps it across image upgrades; on a release-archive install it survives
swapping the release directory.

A file-based install that upgrades in place keeps its existing pair — the
upgrade command copies it to the new location. If you upgrade by hand and skip
`unopim:upgrade`, the old pair is still read from where it has always been, so
the API keeps working until you run the command. A container upgrade cannot do
either: the old image layer holding the keys is gone before the upgrade runs,
so a new pair is generated and clients re-authenticate once.

The pair is created by `php artisan unopim:passport:keys`, which
`unopim:install` and `unopim:upgrade` both run. It never overwrites existing
keys. Set `UNOPIM_OAUTH_KEY_PATH` to relocate it, or supply
`PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` to load the keys from your
secret manager instead of from disk.

### Extensions and custom code

If you maintain custom packages or theme overrides, check them against the
BC Breaks section of the [CHANGELOG](CHANGELOG.md#bc-breaks). The ones that
most often bite:

| Change | What breaks |
|---|---|
| Laravel 12 → 13, Symfony 8 | Overridden method signatures, middleware references, service providers |
| Admin URL changes | Hardcoded links to `catalog/families/*`, `catalog/attributegroups/*`, `settings/data-transfer/*`, `integrations/api-keys/*` |
| AJAX navigation and the global save bar | Browser tests and extensions that assume full-page reloads or old DOM IDs |
| Variant value storage | Extensions reading `products.values` directly must use `Product::resolvedValues()` |
| Removed classes and services | `Webkul\FPC`, `spatie/laravel-responsecache`, `ThemeCustomizationRepository`, old webhook settings classes |

### Take a backup you have restored before

The upgrade writes and verifies its own database dump, and refuses to continue
if it cannot. That is a safety net, not a backup strategy. Keep your own copy
of the database and the `storage/` directory somewhere off the server.

---

<a name="how-the-upgrade-works"></a>
## How the upgrade works

The new release is installed **beside** the current one, not on top of it:

```
/var/www/unopim/
├── releases/2.1.6/     ← keeps running while you prepare
├── releases/3.0.0/     ← the new release
└── current -> releases/3.0.0
```

Your web server document root points at `current/public`. Cutover is a symlink
move. Rollback is a symlink move back.

This matters. Overwriting files in place leaves deleted files behind, destroys
your `config/` and `composer.json` edits, and puts live traffic on a
half-swapped application. None of that can happen when the new release is a
separate directory.

`php artisan unopim:upgrade` then does the rest. It runs in five phases, and
**the first three change nothing**:

| Phase | What it does |
|---|---|
| 1. Preflight | PHP version, extensions, database, installed release, pending migrations, running jobs, writable paths, disk space. Any failure stops here — your site is still up and untouched. |
| 2. Drift | Lists the `.env` keys, config files and Composer requirements you must merge by hand. It never edits them. |
| 3. Sizing | Counts the rows that will be migrated, names the irreversible migrations, and estimates the maintenance window. |
| 4. Migration | Verified database dump → maintenance mode → `migrate --force` → caches cleared → workers restarted. |
| 5. Verification | Confirms the data migrations actually landed. On failure the site stays in maintenance mode and a restore recipe is printed. |

Run phases 1–3 as often as you like, days in advance, with `--dry-run`.

---

<a name="track-a"></a>
## 📦 Track A — release archive

### 1. Prepare the directory layout

If you are not already using a `releases/` + `current` layout, create it once:

```bash
cd /var/www/unopim

mkdir -p releases
mv your-existing-installation releases/2.1.6
ln -s releases/2.1.6 current
```

Point your web server at `/var/www/unopim/current/public` and reload it. Nothing
has changed yet — the same code is serving from a new path.

### 2. Extract the new release

```bash
cd /var/www/unopim/releases

curl -fL -o unopim.zip https://github.com/unopim/unopim/archive/refs/tags/v3.0.0.zip
unzip -q unopim.zip
mv unopim-3.0.0 3.0.0
rm unopim.zip
```

### 3. Bring over your environment and files

```bash
cd /var/www/unopim/releases/3.0.0

cp ../2.1.6/.env .env
cp -a ../2.1.6/storage/app/. storage/app/
```

Do **not** copy `config/`, `composer.json`, `vendor/`, or
`storage/framework/`. Phase 2 tells you exactly what to merge from the old
`config/` and `composer.json`.

### 4. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 5. Check before you commit to anything

```bash
php artisan unopim:upgrade --dry-run --from=/var/www/unopim/releases/2.1.6
```

Read all three phases. Fix any preflight failure, merge what phase 2 lists, and
use the phase 3 estimate to book your maintenance window.

### 6. Run the upgrade

```bash
php artisan unopim:upgrade --from=/var/www/unopim/releases/2.1.6
```

Stop your queue workers first if they are managed outside Supervisor. The
command aborts on its own if a tracked import or export job is still running.

### 7. Cut over

```bash
cd /var/www/unopim
ln -sfn releases/3.0.0 current

sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx        # or: sudo systemctl reload apache2
sudo supervisorctl restart unopim-worker
```

---

<a name="track-b"></a>
## 🌿 Track B — Git checkout

Same model, using a worktree instead of an archive. Useful when you carry local
commits.

```bash
cd /var/www/unopim/releases/2.1.6

git fetch --tags
git worktree add ../3.0.0 v3.0.0
```

Then continue from **Track A step 3**. Your local commits stay on their branch;
merge or rebase them onto the tag in the new worktree before running the
upgrade, and let phase 2 confirm nothing was left behind.

---

<a name="track-c"></a>
## 🐳 Track C — Docker

Containers are already side-by-side: a new image tag *is* a new release. There
is no file copying, and the archive instructions above do not apply.

### 1. Note the compose layout change

`compose.yaml` now runs UnoPim from the published images and needs no `.env`.
The stack that builds from a checkout moved to `compose.dev.yaml`
(`compose.dev.apache.yaml` for Apache, `compose.mysql.yaml` for MySQL on the
image stack). `docker-compose.yml` and `docker-compose.hub.yml` remain as thin
includes.

A bare `docker compose up` in a clone now resolves the **image** stack rather
than building. If you were building from a checkout, switch explicitly:

```bash
docker compose -f compose.dev.yaml up -d
```

### 2. MySQL installations must stay on MySQL

New Docker environments default to PostgreSQL. Changing the profile does not
migrate your data. Keep your existing `COMPOSE_PROFILES`, `DB_CONNECTION`,
`DB_HOST` and `DB_PORT` values exactly as they are.

### 3. Pull and upgrade

```bash
docker compose pull

docker compose run --rm app php artisan unopim:upgrade --dry-run
docker compose run --rm app php artisan unopim:upgrade

docker compose up -d
```

The database lives in a volume, so it survives the image swap. Rollback is
re-pinning the previous image tag and restoring the database dump.

---

<a name="after-the-upgrade"></a>
## ✅ After the upgrade

### Rebuild the Elasticsearch indexes

The upgrade deliberately does **not** reindex: on a large catalog it runs far
longer than the migration itself, and search falls back to the database in the
meantime rather than failing. Run it once the site is back up:

```bash
php artisan unopim:elastic:clear
php artisan unopim:category:index
php artisan unopim:product:index
```

For a small catalog you can fold it into the upgrade with `--with-reindex`.

### Merge what phase 2 reported

Re-apply your `config/` changes and re-add your own Composer requirements.
Add the new `.env` keys — they fall back to defaults, but you should set them
deliberately:

| Key | Why it matters |
|---|---|
| `CORS_ALLOWED_ORIGINS`, `CORS_ALLOWED_METHODS` | Defaults to `*`. Restrict it in production. |
| `REST_API_RATE_LIMIT` | Requests per minute against the REST API. |
| `NOTIFICATIONS_ENABLED` | Turns the notification system on. |
| `MICROSOFT_SSO_*` | Only needed if you enable Microsoft SSO. |

Remove `RESPONSE_CACHE_ENABLED`. It no longer has any effect.

### Re-cache configuration and routes

The upgrade runs `optimize:clear` and does not re-cache, so the new release
starts from source. If your deployment relies on cached config, rebuild it after
cutover:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Reissue API credentials

Every integration now belongs to a robot user. Open each one in the admin panel,
reveal or regenerate its credentials, and update your clients. Existing tokens
stopped working the moment the new signing keys were generated.

### Smoke test

- Log in to the admin panel
- Open a product with variants and save it
- Open the category tree
- Run a product search with and without Elasticsearch
- Run one small import and one export
- Call one REST endpoint with freshly issued credentials

---

<a name="rollback"></a>
## ↩️ Rollback

Because the previous release was never modified, rolling back the application is
one command:

```bash
cd /var/www/unopim
ln -sfn releases/2.1.6 current
sudo systemctl reload php8.4-fpm && sudo systemctl reload nginx
```

The database is the part that needs care. The migrations in this release rewrite
data and **cannot be reversed** — `migrate:rollback` will not restore it.
Restore the dump the upgrade wrote:

```bash
# MySQL
mysql -u your_db_user -p your_db_name < storage/app/upgrade-backups/<dump>.sql

# PostgreSQL
psql -U your_db_user -d your_db_name -f storage/app/upgrade-backups/<dump>.sql
```

Then bring the old release out of maintenance mode:

```bash
cd /var/www/unopim/releases/2.1.6
php artisan up
```

---

<a name="how-long"></a>
## ⏱️ How long will it take?

`--dry-run` gives you a figure for your own data. As a planning guide:

| Catalog size | Migration window | Elasticsearch reindex |
|---|---|---|
| Under 100k products | 5–15 minutes | Minutes, can run inline |
| 100k – 1M products | 20–60 minutes | 30 minutes to a few hours, after cutover |
| Over 1M products | 1–3 hours | Several hours, after cutover |

The migration adds indexes to `products` and `product_completeness`, which holds
long locks on large tables, and backfills product associations row by row. Both
scale with catalog size. The Elasticsearch reindex runs *after* the site is back
up and does not extend your downtime.

---

<a name="command-reference"></a>
## 🛠️ Command reference

```bash
php artisan unopim:upgrade [options]
```

| Option | Effect |
|---|---|
| `--from=PATH` | Path to the previous release directory. Enables the full drift report — without it, config files and Composer requirements cannot be compared. |
| `--dry-run` | Runs phases 1–3 and stops. Changes nothing. |
| `--with-reindex` | Rebuilds Elasticsearch indexes inline. Ignored on catalogs above the configured product limit. |
| `--skip-backup` | Skips the database dump. Only for installations backed up by infrastructure — you lose the automatic restore point. |
| `--force` | Continues when the installed release is older than the supported floor. Untested territory; take a backup you trust. |

Thresholds — the supported version floor, sizing throughputs, required
extensions, the inline reindex limit — live in `config/upgrade.php` and can be
tuned per installation.

---

<a name="roadmap"></a>
## 🧭 Where this is going

UnoPim currently ships its packages inside the application repository, which is
why upgrading means replacing a directory. The direction of travel is to publish
`packages/Webkul/*` as versioned Composer packages so the application becomes a
thin skeleton and upgrades become `composer update`.

That is a repository restructure, not a release note, and it is not part of this
release. The side-by-side layout above is the model either way: when the split
lands, only steps 2 to 4 get shorter.
