# Unopim Connector Development: Agent Instructions

This repository contains reusable agent skills for building Unopim third-party
connector modules (integrations with WooCommerce, Shopify, Shopware, module,
and any REST API). These instructions apply to all AI coding agents (Codex,
Copilot, Claude, Kilo Code, Cursor, etc.).

---

## Framework Context

- **Platform:** Unopim (Webkul) — Laravel 11 modular PIM system
- **Not Bagisto** — Unopim and Bagisto are different products. Do NOT use Bagisto patterns.
- **Package location:** `packages/Webkul/{ModuleName}/src/`
- **Reference implementation:** `packages/Webkul/WooCommerce/` (production reference)

---

## Critical Conventions (Never Deviate)

### Table Naming
- Tables use `DB_PREFIX` (configured in `.env`, default `wk_`) — Laravel adds this automatically
- In migrations, models, and `DB::table()` — use names **WITHOUT** prefix (e.g. `{module}_credentials`)
- Laravel converts `{module}_credentials` → `wk_{module}_credentials` at runtime
- **Never** hardcode `wk_` in table names — it causes `wk_wk_` double prefix issues

### Folder Paths
- Migration folder: `Database/Migration/` at package root — NOT `Migrations` (no 's')
- DataGrid location: `src/DataGrids/{Section}/{Name}DataGrid.php` — always in subdirectory

### ServiceProvider
```php
// Routes: use Route::middleware — NEVER $this->loadRoutesFrom()
Route::middleware('web')->group(__DIR__ . '/../../Routes/{module-name}-routes.php');

// Event name ends with .before — NEVER omit it
Event::listen('unopim.admin.layout.head.before', ...);
```

### Route Middleware
- Use `['admin']` only — NEVER `['web', 'admin']`

### Models (HistoryTrait required)
```php
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;

class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $table = '{module}_credentials';  // No wk_ — DB_PREFIX is added automatically
    protected $casts = ['extras' => 'array'];
    protected $auditExclude = ['consumerSecret'];   // NOT Crypt::encryptString
}
```

### ModuleServiceProvider (model binding)
```php
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [\Webkul\{ModuleName}\Models\Credential::class];
}
// Register it inside main ServiceProvider::register()
```

### ACL — Flat array, no children
```php
return [
    ['key' => 'woocommerce', 'name' => '...', 'route' => '...', 'sort' => 1],
    ['key' => 'woocommerce.credentials', ...],
    // NO nested 'children' arrays
];
```

### Controllers return JsonResponse
```php
// store/update/delete return JSON with redirect_url
return new JsonResponse([
    'redirect_url' => route('{module-slug}.credentials.index'),
    'message' => '...',
]);
```

### FormRequest (never inline validate)
```php
// CORRECT: type-hint the FormRequest
public function store(CredentialForm $request): JsonResponse { ... }

// WRONG: inline validation
public function store(Request $request) {
    $request->validate([...]); // Never do this
}
```

### HTTP Client (cURL, not Guzzle)
```php
// CORRECT
$ch = curl_init();
curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true]);
$response = curl_exec($ch);
curl_close($ch);

// WRONG — Guzzle is NOT used in Unopim connectors
$client = new \GuzzleHttp\Client();
$response = $client->get($url);
```

### DataGrid Callbacks
```php
// Column closure: arrow function OK
'closure' => fn ($row) => $row->status ? '<span class="label-active">Yes</span>' : '<span class="label-info text-gray-600 dark:text-gray-300">No</span>',

// Action URL: regular function required (must return string)
'url' => function ($row) {
    return route('{module-slug}.credentials.edit', $row->id);
},
```

### DataGrid Methods: PHPDoc return types only
```php
// CORRECT
/**
 * @return \Illuminate\Database\Query\Builder
 */
public function prepareQueryBuilder() { ... }

// WRONG — no PHP return type hints on DataGrid methods
public function prepareQueryBuilder(): Builder { ... }
```

### Exporter Filter Fields (4 required keys)
```php
// Every select-type filter field MUST have all 4 of these
[
    'name'       => 'credential',
    'type'       => 'select',
    'async'      => true,                       // required
    'track_by'   => 'id',                       // required
    'label_by'   => 'label',                    // required
    'list_route' => '{module-slug}.credentials.get',  // required route name
]
```

### Three Config Files
All connectors must have all three:
- `exporters.php` — scheduled/manual export jobs
- `quick_exporters.php` — one-click export from product listing
- `importers.php` — import jobs

---

## Skills Available

Load these at the start of your task for detailed implementation guidance:

| Goal | Skill name |
|---|---|
| Start a connector from scratch | `unopim-connector-quickstart` |
| Full module boilerplate | `unopim-package` |
| Credential CRUD + model | `unopim-credential-management` |
| HTTP client (cURL) | `unopim-http-client` |
| Export/import workflow | `unopim-export-workflow` |
| DataGrid listing | `unopim-datagrid` |
| module mapping | `unopim-connector-export-mapping` |

Skills are located in `.kilocode/skills-code/` (Kilo Code),
`.claude/skills/` (Claude), and `.github/skills/` (other agents).

---

## Code Quality Standards

- PSR-12 code style
- All public methods have PHPDoc blocks
- Translations use lang files — never hardcode UI strings
- `extras` JSON column for flexible config (never add many one-off columns)
- All sensitive fields in `$auditExclude` — never in `Crypt::encryptString`
- Service class mediates all API calls — controllers are thin

---

## Do NOT

- Use Bagisto patterns (different platform)
- Use Guzzle — use native cURL
- Use `Crypt::encryptString()` for API secrets — use `$auditExclude`
- Use `$this->loadRoutesFrom()` — use `Route::middleware('web')->group()`
- Omit `.before` from the layout event name
- Use `['web', 'admin']` middleware — use `['admin']` only
- Create DataGrid in root `DataGrids/` — always use a subdirectory
- Use nested `children` in ACL config
- Use inline `$request->validate()` in controllers

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v12
- laravel/octane (OCTANE) - v2
- laravel/passport (PASSPORT) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== octane/core rules ===

# Octane

- Octane boots the application once and reuses it across requests, so singletons persist between requests.
- The Laravel container's `scoped` method may be used as a safe alternative to `singleton`.
- Never inject the container, request, or config repository into a singleton's constructor; use a resolver closure or `bind()` instead:

```php
// Bad
$this->app->singleton(Service::class, fn (Application $app) => new Service($app['request']));

// Good
$this->app->singleton(Service::class, fn () => new Service(fn () => request()));
```

- Never append to static properties, as they accumulate in memory across requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
