---
name: octane-development
description: "Use this skill when working with Laravel Octane, a long-running PHP worker server (Swoole, FrankenPHP, RoadRunner) where the application boots once and serves many requests instead of rebooting for each request like PHP-FPM. Trigger when installing Octane or starting its server; configuring or detecting the active driver for driver-specific code; using Octane::concurrently(), Octane::table(), the Octane cache, or shared in-memory state across workers; controlling worker memory growth; or testing Octane behavior. Skip plain PHP-FPM applications with no persistent worker."
license: MIT
metadata:
  author: laravel
---

# Laravel Octane

Octane boots the application container once and reuses it across requests in a long-running worker. Follow these rules to avoid Octane-specific bugs caused by PHP-FPM assumptions.

Always verify against the docs. Octane's APIs, configuration keys, and driver options change between versions. Before giving setup steps, configuration examples, or exact API syntax, call the Laravel Boost `search-docs` tool scoped to the installed `laravel/octane` version. Treat the rules below as durable patterns, and treat `search-docs` as the source of truth for specifics such as installation commands, Caddyfile and `.rr.yaml` configuration, server flags, and method signatures.

## Detect the Driver First

Read `config('octane.server')` to determine the driver: `swoole`, `roadrunner`, or `frankenphp`. Guard `Octane::concurrently()`, `Octane::table()`, the `octane` cache store, and ticks or intervals behind this check, because they run on Swoole only.

## State Isolation (all drivers)

Avoid storing request-specific state in singletons or static properties. State set during one request can survive into the next, which is the most common source of Octane bugs.

```php
// Avoid: holds request 1's user for every later request
$this->app->singleton(UserContext::class);

// Prefer: flushed and re-resolved per request
$this->app->scoped(UserContext::class);
```

- Scope, lazily resolve, or add to the `flush` list (`config/octane.php`) any service that holds data derived from `$request`, `Auth::user()`, or another per-request value.
- Inject `Request` through method parameters or the `request()` helper inside methods. Do not capture it at construction, where it goes stale.
- Reset any static state you must keep by listening for the `RequestTerminated` event, or list the service in `flush`. Octane already flushes core framework state such as authentication, sessions, and cookies. Your application code is your responsibility.

## Memory (all drivers)

Free what you allocate. Workers are long-lived, so anything left referenced can accumulate until the worker crashes.

- Set a max-requests or max-jobs recycle limit as the last line of defense against leaks.
- Register event listeners in service providers, never inside request handlers, since the dispatcher keeps every one.
- Clear static arrays in a lifecycle listener or `flush` the service. Never append to them in request handlers.
- Watch for singletons that capture large objects (Eloquent models) in closures, since they are held forever.

## Concurrency (Swoole only)

Guard the driver, then run closures in parallel:

```php
[$users, $orders] = Octane::concurrently([
    fn () => User::all(),
    fn () => Order::pending()->get(),
]);
```

- Pass scalar IDs and re-fetch models inside the closure. Do not capture `$this` or non-serializable state, because closures are serialized for task workers.
- Expect results in input order. Catch exceptions inside closures when you need to transform or log the error before Octane rethrows it.

## Swoole Tables and `octane` Cache (Swoole only)

- Pre-size tables at boot (`'name:maxRows'`). You cannot resize them, and exceeding the maximum may fail silently. Treat them as volatile because data is lost on worker restart; do not use them as a Redis or database replacement.
- Use `incr()` and `decr()` for atomic counters, since there are no transactions.
- Use the `octane` cache store only for ephemeral, high-frequency data. It uses the same shared memory and is not durable caching.

## Testing (all drivers)

- Test correctness rather than parallelism when exercising `concurrently()`, tables, and ticks without a real server, since closures may run sequentially in tests.
- Call `$this->refreshApplication()` between assertions to simulate the per-request scoped-binding flush and prove state does not leak.
- Guard Swoole-specific tests with `extension_loaded('swoole') || extension_loaded('openswoole')` and skip them when neither extension is available.

## Common Pitfalls

These issues recur when developing applications on Octane. Guard against each one as you write code.

- Avoid resolving `config()`, `app('config')`, or other container services in low-level worker boot code before the application is fully bootstrapped, especially when configuration is not cached. Resolve configuration after the application is booted, and never assume `php artisan config:cache` has run.
- Never capture `Auth::user()` in a singleton, static property, or custom guard binding. It can leave one user logged in for later requests on the same worker, even after their cookie is cleared. Resolve authentication state per request and add stateful services to the `flush` list. See [State Isolation](#state-isolation-all-drivers).
- Close per-request Redis, database, and HTTP client connections you open outside the framework's managed pools. Octane does not close them, so they can accumulate until the worker recycles. Reset them in a `RequestTerminated` listener.
- Do not read PHP superglobals. `$_GET`, `$_POST`, and `$_SERVER` are not reliably populated under workers. Use the `Request` object instead.
- Do not capture non-serializable values in a `concurrently()` closure. A `PDO` connection, Eloquent model, or `$this` can trigger a serialization error. Pass scalar IDs and re-fetch models inside the closure.
- Fix PHP warnings emitted during request startup. On FrankenPHP, they can shut down the worker. Resolve the underlying warning rather than suppressing it.
- Reload workers after changing code, providers, environment variables, or configuration. Workers hold the booted application in memory and pick up changes only after `octane:reload`, a restart, or running with `--watch` in local development.

## References

Verify driver-specific details against the official docs, because versions and configuration keys change:

- Laravel Octane: https://laravel.com/docs/octane
- FrankenPHP: https://frankenphp.dev/docs/ (worker mode: `/docs/worker/`, Laravel: `/docs/laravel/`, config/Caddyfile: `/docs/config/`, known issues: `/docs/known-issues/`)
- OpenSwoole: https://openswoole.com/docs
- RoadRunner: https://docs.roadrunner.dev/docs (PHP workers: `/docs/php-worker/worker.md`, intro: `/docs/general/about.md`)

## Verification

1. Confirm `config('octane.server')` matches the driver assumed by any Swoole-only code.
2. Confirm no request-derived state lives in a `singleton` or static without `scoped`, `flush`, or a reset.
3. Confirm a worker recycle limit (`max_requests` or `max_jobs`) is configured.
