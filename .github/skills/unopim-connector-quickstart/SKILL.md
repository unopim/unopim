---
name: unopim-connector-quickstart
description: >
  Day-1 checklist and step-by-step guide to build a complete Unopim
  third-party connector (WooCommerce, Shopify, Shopware, module, or any REST
  API) within one working day. Use this skill when starting a new Unopim
  integration, planning a connector from scratch, or needing the full ordered
  sequence of steps to create a production-ready connector module. Covers all
  required files, patterns, and critical checkpoints verified against the
  WooCommerce connector reference implementation.
version: "2.0.0"
tags: [unopim, connector, integration, quickstart, scaffold, woocommerce, shopify, shopware]
---

# Unopim Connector Quickstart: Day-1 Guide

## Reference Implementation

All patterns in this guide are verified against the production WooCommerce
connector: `packages/Webkul/WooCommerce/`. When in doubt, refer to that
package's source code.

---

## Prerequisites (15 min)

## Admin UI Rule (CRITICAL)

For all admin Blade forms in connector modules, use UnoPim Blade components first.

- Use `x-admin::form.control-group` wrappers.
- Use `x-admin::form.control-group.label` and `x-admin::form.control-group.error`.
- Use `x-admin::form.control-group.control` for inputs/selects/textareas.
- Do not generate raw `<select>`, `<input>`, `<textarea>`, or `<label>` markup when component equivalents are available.
- Use translations for all labels/placeholders/messages.

For select fields, use component select (`type="select"`) with `:options="json_encode(...)"`, `track-by`, `label-by`, and Vue `@input` binding.

Before coding:
1. Confirm the external API authentication type (Basic Auth / Bearer Token / OAuth)
2. Note the API base URL pattern (e.g. `/wp-json/wc/v3/`, `/api/v1/`, etc.)
3. Decide what entities to sync: Products, Categories, Attributes, Customers, Orders
4. List which direction: export only / import only / bidirectional

---

## Phase 1 — Scaffolding (1–2 hours)

### Step 1: Create directory structure

```
packages/Webkul/{ModuleName}/
├── Config/
├── Routes/
├── Database/
│   ├── Migration/              ← NOT "Migrations" (singular)
│   └── Factories/
├── Resources/
│   ├── lang/en/
│   └── views/
└── src/
    ├── Providers/
    ├── Models/
    ├── Contracts/
    ├── Repositories/
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── Client/
    ├── DataGrids/
    │   └── Credential/
    ├── Services/
    ├── Helpers/
    │   ├── Exporters/
    │   │   ├── Product/
    │   │   ├── Category/
    │   │   └── Attribute/
    │   └── Importers/
    │       └── Product/
    ├── Validators/
    │   └── JobInstances/
    │       ├── Export/
    │       └── Import/
    └── Presenters/
```

### Step 2: composer.json

```json
{
    "name": "webkul/{module-name}",
    "type": "library",
    "autoload": {
        "psr-4": { "Webkul\\{ModuleName}\\": "src/" }
    },
    "extra": {
        "laravel": {
            "providers": ["Webkul\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider"]
        }
    }
}
```

### Step 3: {ModuleName}ServiceProvider

**Critical rules:**
- Routes: `Route::middleware('web')->group(...)` — NOT `$this->loadRoutesFrom()`
- Event: `unopim.admin.layout.head.before` — NOT without `.before`
- Register `ModuleServiceProvider` inside `register()`

```php
public function boot(): void
{
    Route::middleware('web')->group(__DIR__ . '/../../Routes/{module-name}-routes.php');
    $this->loadViewsFrom(...);
    $this->loadTranslationsFrom(...);
    $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migration');

    Event::listen('unopim.admin.layout.head.before', function ($viewRenderEventManager) {
        $viewRenderEventManager->addTemplate('{module-name}::layouts.head');
    });
}

public function register(): void
{
    $this->app->register(ModuleServiceProvider::class);
    $this->registerConfig();
}

protected function registerConfig(): void
{
    $this->mergeConfigFrom(..., 'acl');
    $this->mergeConfigFrom(..., 'menu');
    $this->mergeConfigFrom(..., 'exporters');
    $this->mergeConfigFrom(..., 'quick_exporters');
    $this->mergeConfigFrom(..., 'importers');
}
```

### Step 4: ModuleServiceProvider

```php
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\{ModuleName}\Models\Credential::class,
    ];
}
```

---

## Phase 2 — Database & Model (1 hour)

### Step 5: Migration

- File in: `Database/Migration/` (NOT `Database/Migrations/`)
- Table name: `wk_{module}_credentials` (always `wk_` prefix)
- Column names: camelCase matching model `$fillable` (e.g. `apiUrl`, `consumerKey`)
- Include `extras` JSON column for flexible extra config

```php
Schema::create('wk_{module}_credentials', function (Blueprint $table) {
    $table->id();
    $table->string('label');
    $table->string('apiUrl');
    $table->string('consumerKey');
    $table->string('consumerSecret');
    $table->json('extras')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
});
```

### Step 6: Contract Interface

```
src/Contracts/Credential.php   ← interface Credential {}
```

### Step 7: Credential Model

```php
class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    protected $table = 'wk_{module}_credentials';
    protected $casts = ['extras' => 'array', 'status' => 'boolean'];
    protected $auditExclude = ['consumerSecret'];   // never Crypt::encryptString
}
```

### Step 8: CredentialRepository

```php
class CredentialRepository extends Repository
{
    public function model(): string { return Credential::class; }
}
```

---

## Phase 3 — HTTP Client (30 min)

### Step 9: cURL-based ApiClient

Unopim connectors use **native cURL** — NOT Guzzle, NOT Laravel HTTP facade.

```
src/Http/Client/
├── ApiClient.php     ← curl_init / curl_exec / curl_close
└── BasicAuth.php     ← curl_setopt(CURLOPT_HTTPAUTH)
```

Key methods: `configure()`, `buildApiUrl()`, `get()`, `post()`, `put()`, `delete()`

### Step 10: Service class wraps all API calls

```
src/Services/{ModuleName}Service.php
```

Controllers inject `{ModuleName}Service`, never `ApiClient` directly.

---

## Phase 4 — CRUD Controller & Routes (1 hour)

### Step 11: FormRequest

```
src/Http/Requests/CredentialForm.php
```

Never use inline `$request->validate()` in controllers.

### Step 12: CredentialController

- Returns `JsonResponse` with `redirect_url` for store/update/delete
- Inject `CredentialRepository` and `{ModuleName}Service`
- `testConnection()` method for API connectivity check

### Step 13: Routes

```php
// Middleware: ['admin'] only — NOT ['web', 'admin']
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    // credentials CRUD + test-connection
    // credentials/get  ← required for exporters filter list_route
});
```

---

## Phase 5 — Admin UI (1 hour)

### Step 14: ACL (flat array — no nested children)

```php
return [
    ['key' => '{module-slug}', 'name' => '...', 'route' => '...', 'sort' => 1],
    ['key' => '{module-slug}.credentials', ...],
    ['key' => '{module-slug}.credentials.create', ...],
    ['key' => '{module-slug}.credentials.edit', ...],
    ['key' => '{module-slug}.credentials.delete', ...],
];
```

### Step 15: DataGrid

```
src/DataGrids/Credential/CredentialDataGrid.php
```

Rules:
- `DB::table('wk_{module}_credentials')` in `prepareQueryBuilder()`
- Methods have PHPDoc `@return` only (no PHP type hints)
- Column `closure` uses `fn ($row) =>` (arrow function)
- Action `url` uses `function ($row) { return route(...); }` (regular function)
- Status badges: `label-active` / `label-info text-gray-600 dark:text-gray-300`

### Step 16: Blade views

```
views/credentials/index.blade.php    ← <x-admin::datagrid :src="route(...)" />
views/credentials/create.blade.php
views/credentials/edit.blade.php
```

---

## Phase 6 — Export/Import Workflow (2 hours)

### Step 17: exporters.php (every select field MUST have these 4 keys)

```php
[
    'name'       => 'credential',
    'type'       => 'select',
    'async'      => true,         ← required
    'track_by'   => 'id',         ← required
    'label_by'   => 'label',      ← required
    'list_route' => '{module-slug}.credentials.get',  ← required
]
```

### Step 18: quick_exporters.php (one-click from product listing)

```php
'{ModuleName}QuickExport' => [
    'title'    => '...',
    'route'    => '{module-slug}.quick_export',
    'exporter' => ...,
    'source'   => \Webkul\Product\Repositories\ProductRepository::class,
];
```

### Step 19: importers.php (same filter field structure as exporters)

### Step 20: Exporter & Importer classes

```
src/Helpers/Exporters/{Entity}/Exporter.php   ← extends AbstractExporter
src/Helpers/Importers/{Entity}/Importer.php   ← extends AbstractImporter
```

Each exporter needs: `BATCH_SIZE`, `UNOPIM_ENTITY_NAME`, `ACTION_ADD`, `ACTION_UPDATE`, `CODE_ALREADY_EXIST`, `CODE_NOT_EXIST`

### Step 21: Validator classes

```
src/Validators/JobInstances/Export/ProductsValidator.php
src/Validators/JobInstances/Import/ProductsValidator.php
```

---

## Phase 7 — Lang & Final Polish (30 min)

### Step 22: Translation file

```php
// Resources/lang/en/app.php
return [
    'credentials' => [
        'create-success' => 'Credential created successfully.',
        'update-success' => 'Credential updated successfully.',
        'delete-success' => 'Credential deleted successfully.',
        'test-success'   => 'Connection successful.',
        'test-failed'    => 'Connection failed. Check credentials.',
    ],
    // acl, menu, data-transfer keys...
];
```

### Step 23: Register provider

Add to `bootstrap/providers.php`:

```php
\Webkul\{ModuleName}\Providers\{ModuleName}ServiceProvider::class,
```

---

## Complete Checklist (in order)

### Scaffolding
- [ ] `composer.json` with correct PSR-4 autoload
- [ ] `{ModuleName}ServiceProvider` with `Route::middleware('web')->group()`
- [ ] Event: `unopim.admin.layout.head.before` (with `.before`)
- [ ] `ModuleServiceProvider` extends `CoreModuleServiceProvider` with `$models[]`
- [ ] All 5 configs merged in `registerConfig()`

### Database
- [ ] Migration in `Database/Migration/` (NOT `Migrations`)
- [ ] Tables use `wk_` prefix
- [ ] camelCase column names match model `$fillable`
- [ ] `extras` JSON column present

### Model
- [ ] `HistoryTrait` + `PresentableHistoryInterface`
- [ ] `$auditExclude` for sensitive fields
- [ ] `'extras' => 'array'` cast
- [ ] Matching `Contracts/` interface

### Routes & Controller
- [ ] Route middleware: `['admin']` only
- [ ] `credentials/get` route for exporter `list_route`
- [ ] Controller uses `FormRequest` (no inline validate)
- [ ] Controller returns `JsonResponse` with `redirect_url`

### HTTP Client
- [ ] `ApiClient.php` uses cURL (no Guzzle)
- [ ] `BasicAuth.php` or equivalent auth class
- [ ] `{ModuleName}Service.php` wraps all API calls

### DataGrid
- [ ] Subdirectory: `DataGrids/Credential/`
- [ ] PHPDoc `@return` only (no PHP type hints on methods)
- [ ] Column `closure` uses `fn ($row) =>`
- [ ] Action `url` uses `function ($row) { return ...; }`

### Export/Import
- [ ] `exporters.php` filter fields have `async/track_by/label_by/list_route`
- [ ] `quick_exporters.php` created
- [ ] `importers.php` created
- [ ] Exporter classes in `Helpers/Exporters/{Entity}/`
- [ ] Validator classes in `Validators/JobInstances/`

### ACL
- [ ] Flat array (no nested `children`)
- [ ] Permission checks use `bouncer()->hasPermission()`

---

## Skill Cross-Reference

| Topic | Skill to use |
|---|---|
| Full module scaffold | `unopim-package` |
| Credential CRUD + model | `unopim-credential-management` |
| cURL HTTP client | `unopim-http-client` |
| Export/import jobs | `unopim-export-workflow` |
| DataGrid listing | `unopim-datagrid` |
| module mapping | `unopim-connector-export-mapping` |
