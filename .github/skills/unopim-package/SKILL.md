---
name: unopim-package
description: >
  Generate complete production-ready Unopim modules (Unopim-style packages)
  including ServiceProvider, ModuleServiceProvider, routes, ACL, menu,
  controllers, repositories, models with HistoryTrait, views, config files
  (exporters / quick_exporters / importers / acl / menu), migrations with wk_
  prefix, factories, presenters, contracts, and composer.json. Use this skill
  when creating new Unopim integration connectors (WooCommerce, Shopify,
  Shopware, module, etc.) or any new Unopim module/package from scratch.
version: "2.0.0"
tags: [unopim, laravel, module, package, scaffold, connector, integration]
---

# Unopim Package: Complete Module Scaffold

## Overview

All Unopim third-party connector packages follow the same structure inspired by
`packages/Webkul/WooCommerce/`. Every pattern here is derived from that
production reference implementation.

**Vendor/Module convention:** `Webkul/{ModuleName}` where `{ModuleName}` is
PascalCase (e.g. `WooCommerce`, `Shopify`, `module`, `ShopwareIntegration`).

**Admin UI rule (critical):**
- For admin Blade forms, always use UnoPim Blade components (`x-admin::form.control-group`, `.label`, `.control`, `.error`).
- Do not generate raw `<select>`, `<input>`, `<textarea>`, or `<label>` controls when a component equivalent exists.
- Keep all user-facing text in translation keys.
- For select fields, use component select with `type="select"`, `:options="json_encode(...)"`, `track-by`, `label-by`, and Vue `@input`.

---

## 1. Directory Structure

```
packages/Webkul/{ModuleName}/
├── composer.json
├── Config/
│   ├── acl.php
│   ├── menu.php
│   ├── exporters.php
│   ├── quick_exporters.php
│   └── importers.php
├── Routes/
│   └── {module-name}-routes.php
├── Database/
│   ├── Migration/                               # NOT "Migrations" — singular
│   │   └── 2025_01_01_000000_wk_{module}_credentials.php
│   └── Factories/
│       └── CredentialFactory.php
├── Resources/
│   ├── lang/
│   │   └── en/
│   │       └── app.php
│   └── views/
│       ├── credentials/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── export/
│           └── export.blade.php
└── src/
    ├── Providers/
    │   ├── {ModuleName}ServiceProvider.php          # Main provider
    │   └── ModuleServiceProvider.php                # CoreModuleServiceProvider
    ├── Models/
    │   ├── Credential.php
    │   └── ...
    ├── Contracts/
    │   ├── Credential.php                           # Interface per model
    │   └── ...
    ├── Repositories/
    │   ├── CredentialRepository.php
    │   └── ...
    ├── Http/
    │   ├── Controllers/
    │   │   ├── CredentialController.php
    │   │   └── ...
    │   ├── Requests/
    │   │   ├── CredentialForm.php                   # FormRequest class
    │   │   └── ...
    │   └── Client/
    │       ├── ApiClient.php                        # cURL-based HTTP client
    │       └── BasicAuth.php
    ├── DataGrids/
    │   └── Credential/
    │       └── CredentialDataGrid.php               # Always in subdirectory
    ├── Services/
    │   └── {ModuleName}Service.php                  # Wraps all API calls
    └── Presenters/
        └── CredentialPresenter.php                  # For HistoryControl display
```

---

## 2. composer.json

```json
{
    "name": "webkul/{module-name}",
    "description": "{Module Name} integration for Unopim",
    "type": "library",
    "require": {
        "php": "^8.1"
    },
    "autoload": {
        "psr-4": {
            "Webkul\\{ModuleName}\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Webkul\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider"
            ]
        }
    }
}
```

---

## 3. Main ServiceProvider

The main ServiceProvider handles booting routes, views, translations, events,
and registering configs.

**CRITICAL patterns:**
- Routes must use `Route::middleware('web')->group(...)` — NOT `$this->loadRoutesFrom()`
- Event name for head injection is `unopim.admin.layout.head.before` (with `.before`)
- Six configs must be registered: `acl`, `menu`, `exporters`, `quick_exporters`, `importers`, plus any custom ones
- `loadTranslationsFrom` and `loadViewsFrom` use the module slug as the namespace

```php
<?php

namespace Webkul\{ModuleName}\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {ModuleName}ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        // Routes — must use Route::middleware('web')->group(), NOT loadRoutesFrom()
        Route::middleware('web')->group(
            __DIR__ . '/../../Routes/{module-name}-routes.php'
        );

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../Resources/views', '{module-name}');

        // Translations
        $this->loadTranslationsFrom(
            __DIR__ . '/../../Resources/lang',
            '{module-name}'
        );

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migration');

        // Inject assets/scripts into admin head
        // IMPORTANT: event name ends with ".before"
        Event::listen('unopim.admin.layout.head.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate(
                '{module-name}::layouts.head'
            );
        });
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Config/acl.php',            'acl');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/menu.php',           'menu');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/exporters.php',      'exporters');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/quick_exporters.php', 'quick_exporters');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/importers.php',      'importers');
    }
}
```

---

## 4. ModuleServiceProvider (Model Binding)

Extends `Webkul\Core\Providers\CoreModuleServiceProvider`. Only declare the
`$models` array — never write manual `$app->bind()` calls.

```php
<?php

namespace Webkul\{ModuleName}\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models to register their repository bindings.
     *
     * @var array
     */
    protected $models = [
        \Webkul\{ModuleName}\Models\Credential::class,
        // add more models here
    ];
}
```

Register both providers in `{ModuleName}ServiceProvider::register()`:

```php
public function register(): void
{
    $this->app->register(ModuleServiceProvider::class);
    $this->registerConfig();
}
```

---

## 5. Model (with HistoryTrait)

Every model must use `HistoryTrait` from `Webkul\HistoryControl` and implement
`PresentableHistoryInterface`. Sensitive fields must be excluded via
`$auditExclude`. Use an `extras` JSON column for flexible additional config.

```php
<?php

namespace Webkul\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\{ModuleName}\Contracts\Credential as CredentialContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Table name — always use wk_ prefix.
     *
     * @var string
     */
    protected $table = 'wk_{module}_credentials';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'label',
        'apiUrl',
        'consumerKey',
        'consumerSecret',   // secret — excluded from history below
        'extras',
        'status',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'extras' => 'array',
    ];

    /**
     * Fields excluded from history audit.
     * Sensitive values should never appear in history log.
     *
     * @var array
     */
    protected $auditExclude = [
        'consumerSecret',
    ];

    /**
     * History auditable attributes (subset shown in history UI).
     *
     * @var array
     */
    protected $historyAuditable = [
        'label',
        'apiUrl',
        'consumerKey',
        'status',
    ];
}
```

---

## 6. Contract Interface

Every model must have a matching interface in `src/Contracts/`.

```php
<?php

namespace Webkul\{ModuleName}\Contracts;

interface Credential
{
    // marker interface — methods defined in model
}
```

---

## 7. Routes

Route middleware is `['admin']` only — NOT `['web', 'admin']`.
Webhook routes (if any) must exclude `VerifyCsrfToken`.

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\{ModuleName}\Http\Controllers\CredentialController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {

    Route::prefix('{module-slug}')->name('{module-slug}.')->group(function () {

        // Credentials
        Route::get('credentials',          [CredentialController::class, 'index'])->name('credentials.index');
        Route::get('credentials/create',   [CredentialController::class, 'create'])->name('credentials.create');
        Route::post('credentials',         [CredentialController::class, 'store'])->name('credentials.store');
        Route::get('credentials/{id}/edit',[CredentialController::class, 'edit'])->name('credentials.edit');
        Route::put('credentials/{id}',     [CredentialController::class, 'update'])->name('credentials.update');
        Route::delete('credentials/{id}',  [CredentialController::class, 'destroy'])->name('credentials.destroy');

        // Test connection endpoint
        Route::post('credentials/test-connection', [CredentialController::class, 'testConnection'])
             ->name('credentials.test-connection');
    });

});

// Webhook (no CSRF check)
Route::post('{module-slug}/webhook', [\Webkul\{ModuleName}\Http\Controllers\WebhookController::class, 'handle'])
     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
     ->name('{module-slug}.webhook');
```

---

## 8. ACL Config

Flat array structure — no nested `children` arrays.

```php
<?php

return [
    [
        'key'   => '{module-slug}',
        'name'  => '{module-name}::app.acl.{module-slug}',
        'route' => '{module-slug}.credentials.index',
        'sort'  => 1,
    ],
    [
        'key'   => '{module-slug}.credentials',
        'name'  => '{module-name}::app.acl.credentials',
        'route' => '{module-slug}.credentials.index',
        'sort'  => 1,
    ],
    [
        'key'   => '{module-slug}.credentials.create',
        'name'  => '{module-name}::app.acl.create',
        'route' => '{module-slug}.credentials.create',
        'sort'  => 1,
    ],
    [
        'key'   => '{module-slug}.credentials.edit',
        'name'  => '{module-name}::app.acl.edit',
        'route' => '{module-slug}.credentials.edit',
        'sort'  => 2,
    ],
    [
        'key'   => '{module-slug}.credentials.delete',
        'name'  => '{module-name}::app.acl.delete',
        'route' => '{module-slug}.credentials.destroy',
        'sort'  => 3,
    ],
];
```

---

## 9. Menu Config

```php
<?php

return [
    [
        'key'        => '{module-slug}',
        'name'       => '{module-name}::app.menu.{module-slug}',
        'route'      => '{module-slug}.credentials.index',
        'sort'       => 5,
        'icon'       => 'icon-{module-slug}',
    ],
    [
        'key'        => '{module-slug}.credentials',
        'name'       => '{module-name}::app.menu.credentials',
        'route'      => '{module-slug}.credentials.index',
        'sort'       => 1,
        'icon'       => '',
    ],
];
```

---

## 10. Migration

Table prefix is always `wk_`. Folder is `Database/Migration/` (NOT `Migrations`).
Column names use camelCase to match model `$fillable` exactly.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wk_{module}_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('apiUrl');
            $table->string('consumerKey');
            $table->string('consumerSecret');
            $table->json('extras')->nullable();    // flexible JSON for extra config
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_{module}_credentials');
    }
};
```

---

## 11. App Service Provider Registration

Register the package providers in the Unopim core `config/app.php` or via
package discovery. For local dev, add to `bootstrap/providers.php`:

```php
\Webkul\{ModuleName}\Providers\{ModuleName}ServiceProvider::class,
```

---

## 12. Checklist for New Module

- [ ] `composer.json` created with correct PSR-4 namespace
- [ ] `{ModuleName}ServiceProvider` uses `Route::middleware('web')->group(...)`
- [ ] `{ModuleName}ServiceProvider` registers `ModuleServiceProvider`
- [ ] `ModuleServiceProvider` extends `CoreModuleServiceProvider` with `$models[]`
- [ ] Event name: `unopim.admin.layout.head.before` (never without `.before`)
- [ ] Migration in `Database/Migration/` (not `Migrations`)
- [ ] All table names prefixed with `wk_`
- [ ] All models use `HistoryTrait` + implement `PresentableHistoryInterface`
- [ ] All models have `$auditExclude` for sensitive fields
- [ ] All models have `extras` JSON column with `'extras' => 'array'` cast
- [ ] All models have matching `Contracts/` interface
- [ ] ACL is flat array (no nested `children`)
- [ ] `registerConfig()` merges all 5 config files
- [ ] `quick_exporters.php` and `importers.php` both created
- [ ] Route middleware is `['admin']` only
