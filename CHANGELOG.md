# v1.0.x

## v1.0.1 - 2026-05-25

### Security
- Patched an authorization gap on several admin write-verb routes (`*.store` / `*.update`) — they were not present in `packages/Webkul/Admin/src/Config/acl.php`, so the `Bouncer` middleware never enforced a permission check. Low-privileged admins could submit write requests to catalog, settings, and data-transfer endpoints they could not legitimately view. Mapped each missing route to the same ACL key as its sibling GET form (`.create` / `.edit`) and added regression coverage.
- Hardened against `Host` / `X-Forwarded-Host` header poisoning. Asset and URL helpers (`url()`, `asset()`, Vite) previously resolved against the request `Host` header, so a crafted header could cause the admin layout to load JavaScript from an attacker origin. URL generation is now pinned to `APP_URL` via `URL::forceRootUrl()` + `URL::forceScheme()`, the four templates that rendered `url()->to('/')` / `asset('/')` were switched to `config('app.url')`, `App\Http\Middleware\TrustProxies` now reads from the new `TRUSTED_PROXIES` env variable (defaults to `127.0.0.1`), and `App\Http\Middleware\TrustHosts` is registered in the global middleware stack seeded from `APP_URL` plus the new `TRUSTED_HOSTS` env variable.

## v1.0.0

Initial 1.0 release.
