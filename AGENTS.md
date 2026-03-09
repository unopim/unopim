<unopim-guidelines>
=== foundation rules ===

# UnoPim Guidelines

UnoPim is a Laravel-based open-source Product Information Management (PIM) system. These guidelines are specifically curated for developing with UnoPim and its Concord modular package architecture.

## Foundational Context

This application is an **UnoPim** PIM platform built on Laravel. You must be familiar with both Laravel and UnoPim's modular package architecture built with Concord.

### Technology Stack

- **PHP**: 8.2+
- **Laravel**: v11
- **Vue.js**: 3 (admin panel interactivity)
- **Tailwind CSS**: For styling
- **Vite**: Asset bundling
- **Laravel Pint**: Code formatting (Laravel preset)
- **Pest**: Testing framework (built on PHPUnit)
- **Concord**: Modular package management with proxy models
- **Kalnoy Nested Set**: Category tree structure
- **ElasticSearch**: Product/category indexing

### UnoPim Core Packages

UnoPim uses a modular package structure in `packages/Webkul/`:

| Package | Purpose |
|---------|---------|
| **Admin** | Admin panel: DataGrids, Controllers, Views, Config |
| **AdminApi** | REST API: v1/rest endpoints, OAuth 2.0 |
| **Attribute** | Attribute system: Models, Enums, Rules |
| **Category** | Category tree (nested-set) |
| **Completeness** | Product completeness scoring |
| **Core** | Foundation: Models, Helpers, Facades |
| **DataGrid** | Abstract DataGrid engine for listings |
| **DataTransfer** | Import/Export pipeline with job tracking |
| **ElasticSearch** | Search indexing and query builders |
| **HistoryControl** | Audit trail for entity changes |
| **Installer** | Installation wizard |
| **MagicAI** | AI content generation (OpenAI/Groq/Ollama/Gemini) |
| **Notification** | Event-driven notifications |
| **Product** | Product domain: type strategy (Simple/Configurable) |
| **Theme** | Theme engine and view composition |
| **User** | Admin users and roles |
| **Webhook** | Outgoing webhooks |

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain — don't wait until you're stuck.

- `unopim-backend-dev` — Backend PHP development. Activates when writing PHP code, creating classes, models, repositories, events, listeners, or tests; or when the user mentions model, repository, controller, service, event, listener, observer, or needs to write backend code.

- `unopim-dev-cycle` — Development workflow. Activates when running tests, linting code, building assets, or debugging; or when the user mentions test, lint, pint, build, npm, pest, or needs to verify code quality.

- `unopim-code-review` — Code review. Activates when reviewing code changes, checking standards compliance, or performing PR reviews; or when the user mentions review, standards, conventions, or best practices.

- `unopim-git` — Git operations. Activates when creating branches, commits, or pull requests; or when the user mentions git, branch, commit, PR, pull request, or merge.

- `unopim-plugin-dev` — Plugin development. Activates when creating new packages, extending UnoPim, adding custom importers/exporters, or configuring menus/ACL; or when the user mentions plugin, package, module, extension, importer, exporter, menu, or ACL.

- `unopim-datagrid` — DataGrid development. Activates when creating or modifying listing pages, adding columns, actions, or filters; or when the user mentions datagrid, listing, table, columns, filters, or mass actions.

- `unopim-data-transfer` — Import/export operations. Activates when configuring imports/exports, debugging job pipelines, or creating data transfer profiles; or when the user mentions import, export, CSV, Excel, job, queue, or data transfer.

## UnoPim Architecture

### Package Structure

Every UnoPim package follows a standardized structure:

```
packages/Webkul/{PackageName}/
├── src/
│   ├── Config/
│   │   ├── menu.php           # Admin sidebar menu
│   │   ├── acl.php            # Access control permissions
│   │   └── system.php         # System configuration fields
│   ├── Contracts/             # Model interfaces
│   ├── Database/
│   │   ├── Migrations/
│   │   ├── Seeders/
│   │   └── Factories/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   │   ├── {Model}.php
│   │   └── {Model}Proxy.php   # Concord proxy model
│   ├── Repositories/
│   │   └── {Model}Repository.php
│   ├── Resources/
│   │   ├── views/
│   │   └── lang/
│   ├── Providers/
│   │   ├── {Package}ServiceProvider.php
│   │   └── ModuleServiceProvider.php
│   └── Routes/
└── tests/
```

### Repository Pattern

UnoPim uses the Concord Repository pattern. Always use repositories for data access:

```php
// Correct — use repository
$product = $this->productRepository->findOrFail($id);
$product = $this->productRepository->create($data);

// Avoid direct model access in controllers
$product = Product::find($id); // Less preferred
```

### Concord Proxy Models

Every model must have a Contract (interface) and Proxy class. This allows model swapping without modifying core:

```php
// Override any core model
$this->app->concord->registerModel(
    \Webkul\Product\Contracts\Product::class,
    \App\Models\CustomProduct::class
);
```

### Service Providers

Service providers must:
- Load routes in `boot()`
- Load migrations, translations, and views in `boot()`
- Merge package configuration in `register()` (`menu.admin`, `acl`, `importers`, `exporters`)
- Register observers and event listeners

## Conventions

- Always follow existing code conventions used in this application.
- Use descriptive names for variables and methods.
- Check for existing components to reuse before writing new ones.
- Use PHPDoc blocks for all classes and public/protected methods.
- Follow the package structure when creating new packages.
- Use repositories for database operations, not direct model queries.
- Use `core()` helper for locale, channel, and configuration access.

## Verification

- Do not create verification scripts when tests cover that functionality.
- Unit and feature tests are more important than manual verification.
- Always run `./vendor/bin/pest` to verify changes work.

## Application Structure

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.
- Custom packages should be placed in `packages/Webkul/`.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, they may need to run `npm run build` or `npm run dev`.
- Use `@unoPimVite()` Blade directive for loading built assets.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations — focus on what's important rather than explaining obvious details.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8.1+ constructor property promotion in `__construct()`.
    - `public function __construct(protected ProductRepository $productRepository) {}`
- Do not allow empty `__construct()` methods with zero parameters.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.
- Use PHP 8.1+ enums where appropriate (see `Attribute/src/Enums/`).

```php
protected function validateAttribute(Attribute $attribute, ?string $value = null): bool
{
    ...
}
```

## Enums

- Keys in an Enum should be UPPER_CASE for backed enums (UnoPim convention).
- Example: `SwatchTypeEnum::TEXT`, `SwatchTypeEnum::COLOR`, `SwatchTypeEnum::IMAGE`.

## Comments

- Prefer PHPDoc blocks over inline comments.
- Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Keep descriptions concise — one line is ideal.
- Add `@param`, `@return`, and `@throws` annotations.
- Use fully qualified class names in annotations.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed.
- Use `./vendor/bin/pest --filter=testName` for targeted testing.
- CRITICAL: ALWAYS activate `unopim-backend-dev` skill before writing any test files.

=== laravel rules ===

# Do Things the Laravel Way

- Use Laravel conventions and patterns.
- All new code goes in `packages/Webkul/{Package}/src/`.

## Database

- Always use proper Eloquent relationship methods with return type hints.
- Use Eloquent models and relationships before raw database queries.
- Use Repository pattern for all data access in UnoPim packages.
- Avoid `DB::` facade; prefer repository methods or `Model::query()`.
- Generate code that prevents N+1 query problems by using eager loading.
- Always use `$fillable` on models, never `$guarded = []`.

### Model Creation

- Every new model needs: Contract (interface), Model class, Proxy class.
- Register in `ModuleServiceProvider` extending `BaseModuleServiceProvider`.
- Create factories for test data generation.

## Controllers & Validation

- Use Form Request classes for validation when complexity warrants it.
- For simpler validations, inline validation in controllers is acceptable (following existing patterns).

## Authentication & Authorization

- Use ACL checks via `bouncer()->hasPermission()` for admin actions.
- All admin routes must have ACL entries in `Config/acl.php`.

## URL Generation

- When generating links, use named routes and the `route()` function.
- Route names follow dot-separated convention: `admin.catalog.products.index`.

## Queues

- Use queued jobs for import/export operations with the `ShouldQueue` interface.
- Queue worker command: `php artisan queue:work --queue="default,system"`.

## Configuration

- Use environment variables only in configuration files.
- Always use `config('key')` or `core()->getConfigData('key')`, not `env()` directly.

## Events

- Events follow `{domain}.{entity}.{action}.{before|after}` naming convention.
- Always dispatch events before and after CRUD operations.

=== pint rules ===

# Laravel Pint Code Formatter

- You must run `./vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- For full project formatting: `./vendor/bin/pint`
- For checking without fixing: `./vendor/bin/pint --test`
- Configuration is in `pint.json` at project root (Laravel preset with aligned `=>` operators).

=== pest rules ===

# Pest Testing

- This project uses Pest for testing.
- Run tests: `./vendor/bin/pest` or filter: `./vendor/bin/pest --filter=testName`.
- Run specific suite: `./vendor/bin/pest --testsuite="Admin Feature Test"`.
- Do NOT delete tests without approval.
- IMPORTANT: Activate `unopim-backend-dev` skill every time you're working with a testing-related task.

## Assertions

Use specific assertions when possible:

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |
| `assertRedirect()` | `assertStatus(302)` |
| `assertDatabaseHas()` | Manual DB queries |
| `assertDatabaseMissing()` | Manual DB queries |

## Architecture Testing

Pest supports architecture testing to enforce code conventions:

```php
arch('models should extend Model')
    ->expect('Webkul\Product\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('repositories extend base repository')
    ->expect('Webkul\Product\Repositories')
    ->toExtend('Webkul\Core\Eloquent\Repository');
```

=== elasticsearch rules ===

# ElasticSearch

- Product and category indexing is managed via ElasticSearch.
- Index commands: `php artisan unopim:product:index`, `php artisan unopim:category:index`.
- Clear indexes: `php artisan unopim:elastic:clear`.
- Configure via `.env`: `ELASTICSEARCH_ENABLED`, `ELASTICSEARCH_HOST`, `ELASTICSEARCH_INDEX_PREFIX`.

=== artisan rules ===

# Artisan Commands

Key UnoPim artisan commands:

```bash
php artisan unopim:install                    # Full installation
php artisan unopim:install:default-user       # Create default admin
php artisan unopim:product:index              # Index products in ES
php artisan unopim:category:index             # Index categories in ES
php artisan unopim:elastic:clear              # Clear ES indexes
php artisan unopim:queue:work {id} {email}    # Run specific job
php artisan unopim:purge-unused-images        # Clean orphaned media
php artisan queue:work --queue="default,system" # Start queue worker
php artisan optimize:clear                    # Clear all caches
```

</unopim-guidelines>
