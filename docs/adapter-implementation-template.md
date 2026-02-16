# Adapter Implementation Template Guide

**Version:** 1.0
**Purpose:** Complete reference for implementing full-featured channel adapters in UnoPim
**Based On:** Shopify adapter (gold standard) + Salla adapter (POC implementation)

---

## Overview

This guide provides copy-paste templates for creating a complete adapter package with ~30 files following UnoPim architecture patterns.

**Time Estimate:** 4-6 hours per adapter (using these templates)

**Adapters Remaining:**
- [ ] Amazon (SP-API, multi-region, LWA OAuth2)
- [ ] eBay (OAuth2, site ID, Inventory API)
- [ ] Noon (Dual-header auth, marketplace selection)
- [ ] WooCommerce (REST API v3, consumer key/secret)
- [ ] Magento2 (REST API V1, admin token, store views)
- [ ] EasyOrders (Commission tracking, RESTful API)

---

## File Structure (30 files per adapter)

```
packages/Webkul/{Adapter}/
├── src/
│   ├── Adapters/
│   │   └── {Adapter}Adapter.php [EXISTING - ENHANCE]
│   ├── Contracts/
│   │   ├── {Adapter}CredentialsConfig.php
│   │   ├── {Adapter}MappingConfig.php
│   │   ├── {Adapter}ExportMappingConfig.php
│   │   └── {Adapter}ProductMapping.php
│   ├── Models/
│   │   ├── {Adapter}CredentialsConfig.php
│   │   ├── {Adapter}CredentialsConfigProxy.php
│   │   ├── {Adapter}MappingConfig.php
│   │   ├── {Adapter}MappingConfigProxy.php
│   │   ├── {Adapter}ExportMappingConfig.php
│   │   ├── {Adapter}ExportMappingConfigProxy.php
│   │   ├── {Adapter}ProductMapping.php
│   │   └── {Adapter}ProductMappingProxy.php
│   ├── Repositories/
│   │   ├── {Adapter}CredentialRepository.php
│   │   ├── {Adapter}MappingRepository.php
│   │   ├── {Adapter}ExportMappingRepository.php
│   │   └── {Adapter}ProductMappingRepository.php
│   ├── Http/Controllers/
│   │   ├── CredentialController.php
│   │   ├── MappingController.php
│   │   ├── ImportMappingController.php
│   │   ├── SettingController.php
│   │   └── OptionController.php
│   ├── Database/Migration/
│   │   ├── 2024_08_12_140143_wk_{adapter}_credentials_config.php
│   │   ├── 2024_08_28_171557_wk_{adapter}_data_mapping.php
│   │   ├── 2024_08_28_171558_wk_{adapter}_export_mapping.php
│   │   └── 2024_12_01_000001_create_{adapter}_product_mappings_table.php
│   ├── Config/
│   │   ├── acl.php
│   │   ├── menu.php
│   │   ├── unopim-vite.php
│   │   ├── exporters.php
│   │   └── importers.php
│   ├── Routes/
│   │   └── {adapter}-routes.php
│   ├── Resources/
│   │   ├── lang/en_US/app.php
│   │   └── views/
│   │       ├── credential/index.blade.php
│   │       ├── credential/edit.blade.php
│   │       └── ... (additional views as needed)
│   └── Providers/
│       └── {Adapter}ServiceProvider.php [EXISTING - ENHANCE]
```

---

## STEP 1: Create Contracts (4 files)

### Template: `src/Contracts/{Adapter}CredentialsConfig.php`
```php
<?php

namespace Webkul\{Adapter}\Contracts;

interface {Adapter}CredentialsConfig {}
```

**Apply to:** `{Adapter}MappingConfig`, `{Adapter}ExportMappingConfig`, `{Adapter}ProductMapping`

---

## STEP 2: Create Models (8 files)

### Template: `src/Models/{Adapter}CredentialsConfig.php`
```php
<?php

namespace Webkul\{Adapter}\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\{Adapter}\Contracts\{Adapter}CredentialsConfig as {Adapter}CredentialsConfigContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class {Adapter}CredentialsConfig extends Model implements {Adapter}CredentialsConfigContract
{
    use BelongsToTenant;

    protected $table = 'wk_{adapter}_credentials_config';

    protected $fillable = [
        // Platform-specific credential fields
        'access_token',
        'refresh_token',
        'expires_at',
        'active',
        'store_name',
        'store_locale_mapping',
        'store_locales',
        'default_set',
        'extras',
    ];

    protected $casts = [
        'store_locale_mapping' => 'array',
        'store_locales' => 'array',
        'extras' => 'array',
        'active' => 'boolean',
        'default_set' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
```

**Customize `$fillable` for each platform:**
- **Amazon:** `seller_id`, `marketplace_id`, `region`, `sp_api_refresh_token`, `lwa_access_token`
- **eBay:** `oauth_token`, `oauth_token_secret`, `site_id`, `user_token`
- **Noon:** `partner_id`, `api_key`, `marketplace_code`
- **WooCommerce:** `store_url`, `consumer_key`, `consumer_secret`, `version`
- **Magento2:** `base_url`, `admin_token`, `store_code`
- **EasyOrders:** `merchant_id`, `api_key`, `commission_rate`

### Template: `src/Models/{Adapter}CredentialsConfigProxy.php`
```php
<?php

namespace Webkul\{Adapter}\Models;

use Konekt\Concord\Proxies\ModelProxy;

class {Adapter}CredentialsConfigProxy extends ModelProxy
{
}
```

**Apply same pattern to:**
- `{Adapter}MappingConfig` / `Proxy`
- `{Adapter}ExportMappingConfig` / `Proxy`
- `{Adapter}ProductMapping` / `Proxy` (see special template below)

### Special Template: `src/Models/{Adapter}ProductMapping.php`
```php
<?php

namespace Webkul\{Adapter}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\{Adapter}\Contracts\{Adapter}ProductMapping as {Adapter}ProductMappingContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class {Adapter}ProductMapping extends Model implements {Adapter}ProductMappingContract
{
    use BelongsToTenant;

    protected $table = '{adapter}_product_mappings';

    protected $fillable = [
        'product_id',
        'connector_id',
        'external_id',
        'external_sku',
        'external_parent_id',
        'variant_data',
        'sync_status',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'variant_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

## STEP 3: Create Repositories (4 files)

### Template: `src/Repositories/{Adapter}CredentialRepository.php`
```php
<?php

namespace Webkul\{Adapter}\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\{Adapter}\Contracts\{Adapter}CredentialsConfig;

class {Adapter}CredentialRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return {Adapter}CredentialsConfig::class;
    }
}
```

**Apply to:** `{Adapter}MappingRepository`, `{Adapter}ExportMappingRepository`, `{Adapter}ProductMappingRepository`

---

## STEP 4: Create Migrations (4 files)

### Template: `src/Database/Migration/2024_08_12_140143_wk_{adapter}_credentials_config.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wk_{adapter}_credentials_config', function (Blueprint $table) {
            $table->id()->autoIncrement();
            // Platform-specific credential columns
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(false);
            $table->string('store_name')->nullable();
            $table->json('store_locale_mapping')->nullable();
            $table->json('store_locales')->nullable();
            $table->boolean('default_set')->default(false);
            $table->json('extras')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wk_{adapter}_credentials_config');
    }
};
```

**Customize columns for each platform** (see fillable arrays from models section).

### Template: Product Mapping Migration
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{adapter}_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('connector_id')->constrained('channel_connectors')->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->string('external_parent_id')->nullable();
            $table->json('variant_data')->nullable();
            $table->string('sync_status')->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'connector_id'], '{adapter}_product_connector_unique');
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{adapter}_product_mappings');
    }
};
```

---

## STEP 5: Create Config Files (5 files)

### Template: `src/Config/acl.php`
```php
<?php

return [
    [
        'key'   => '{adapter}',
        'name'  => '{adapter}::app.components.layouts.sidebar.{adapter}',
        'route' => '{adapter}.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => '{adapter}.credentials',
        'name'  => '{adapter}::app.components.layouts.sidebar.credentials',
        'route' => '{adapter}.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => '{adapter}.credentials.create',
        'name'  => '{adapter}::app.{adapter}.acl.credential.create',
        'route' => '{adapter}.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => '{adapter}.credentials.edit',
        'name'  => '{adapter}::app.{adapter}.acl.credential.edit',
        'route' => '{adapter}.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => '{adapter}.credentials.delete',
        'name'  => '{adapter}::app.{adapter}.acl.credential.delete',
        'route' => '{adapter}.credentials.delete',
        'sort'  => 3,
    ], [
        'key'   => '{adapter}.export-mappings',
        'name'  => '{adapter}::app.components.layouts.sidebar.export-mappings',
        'route' => 'admin.{adapter}.export-mappings',
        'sort'  => 2,
    ], [
        'key'   => '{adapter}.import-mappings',
        'name'  => '{adapter}::app.components.layouts.sidebar.import-mappings',
        'route' => 'admin.{adapter}.import-mappings',
        'sort'  => 3,
    ], [
        'key'   => '{adapter}.settings',
        'name'  => '{adapter}::app.components.layouts.sidebar.settings',
        'route' => 'admin.{adapter}.settings',
        'sort'  => 4,
    ],
];
```

### Template: `src/Config/menu.php`
```php
<?php

return [
    [
        'key'   => '{adapter}',
        'name'  => '{adapter}::app.components.layouts.sidebar.{adapter}',
        'route' => '{adapter}.credentials.index',
        'sort'  => 11,
        'icon'  => 'icon-{adapter}',
    ], [
        'key'   => '{adapter}.credentials',
        'name'  => '{adapter}::app.components.layouts.sidebar.credentials',
        'route' => '{adapter}.credentials.index',
        'sort'  => 1,
    ], [
        'key'    => '{adapter}.export-mappings',
        'name'   => '{adapter}::app.components.layouts.sidebar.export-mappings',
        'route'  => 'admin.{adapter}.export-mappings',
        'params' => [1],
        'sort'   => 2,
    ], [
        'key'    => '{adapter}.import-mappings',
        'name'   => '{adapter}::app.components.layouts.sidebar.import-mappings',
        'route'  => 'admin.{adapter}.import-mappings',
        'params' => [3],
        'sort'   => 3,
    ], [
        'key'    => '{adapter}.settings',
        'name'   => '{adapter}::app.components.layouts.sidebar.settings',
        'route'  => 'admin.{adapter}.settings',
        'params' => [2],
        'sort'   => 4,
    ],
];
```

### Template: `src/Config/unopim-vite.php`
```php
<?php

return [
    '{adapter}' => [
        'hot_file'                 => '{adapter}-vite.hot',
        'build_directory'          => 'themes/{adapter}/build',
        'package_assets_directory' => 'src/Resources/assets',
    ],
];
```

### Template: `src/Config/exporters.php`
```php
<?php

return [
    '{adapter}' => [
        'title' => '{AdapterTitle} Product Export',
        'code'  => '{adapter}_product_export',
    ],
];
```

### Template: `src/Config/importers.php`
```php
<?php

return [
    '{adapter}' => [
        'title' => '{AdapterTitle} Product Import',
        'code'  => '{adapter}_product_import',
    ],
];
```

---

## STEP 6: Update Service Provider

### Template: `src/Providers/{Adapter}ServiceProvider.php`
```php
<?php

namespace Webkul\{Adapter}\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\{Adapter}\Adapters\{Adapter}Adapter;

class {Adapter}ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/{adapter}-routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migration');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', '{adapter}');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', '{adapter}');

        $this->registerAdapter();
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/exporters.php',
            'exporters'
        );
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/importers.php',
            'importers'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../Config/unopim-vite.php',
            'unopim-vite.viters'
        );
    }

    /**
     * Register adapter.
     */
    protected function registerAdapter(): void
    {
        $this->app->resolving(AdapterResolver::class, function (AdapterResolver $resolver) {
            $resolver->register('{adapter}', {Adapter}Adapter::class);
        });
    }
}
```

---

## STEP 7: Create Routes

### Template: `src/Routes/{adapter}-routes.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\{Adapter}\Http\Controllers\CredentialController;
use Webkul\{Adapter}\Http\Controllers\ImportMappingController;
use Webkul\{Adapter}\Http\Controllers\MappingController;
use Webkul\{Adapter}\Http\Controllers\OptionController;
use Webkul\{Adapter}\Http\Controllers\SettingController;

Route::group(['middleware' => ['web', 'admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('{adapter}')->group(function () {

        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('', 'index')->name('{adapter}.credentials.index');
            Route::post('create', 'store')->name('{adapter}.credentials.store');
            Route::get('edit/{id}', 'edit')->name('{adapter}.credentials.edit');
            Route::put('update/{id}', 'update')->name('{adapter}.credentials.update');
            Route::delete('delete/{id}', 'destroy')->name('{adapter}.credentials.delete');
        });

        Route::prefix('export')->group(function () {
            Route::controller(SettingController::class)->prefix('settings')->group(function () {
                Route::get('{id}', 'index')->name('admin.{adapter}.settings');
                Route::post('create', 'store')->name('{adapter}.export-settings.create');
            });
            Route::controller(MappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.{adapter}.export-mappings');
                Route::post('create', 'store')->name('{adapter}.export-mappings.create');
            });
        });

        Route::prefix('import')->group(function () {
            Route::controller(ImportMappingController::class)->prefix('mapping')->group(function () {
                Route::get('{id}', 'index')->name('admin.{adapter}.import-mappings');
                Route::post('create', 'store')->name('{adapter}.import-mappings.create');
            });
        });

        Route::controller(OptionController::class)->group(function () {
            Route::get('get-attribute', 'listAttributes')->name('admin.{adapter}.get-attribute');
            Route::get('get-image-attribute', 'listImageAttributes')->name('admin.{adapter}.get-image-attribute');
            Route::get('get-gallery-attribute', 'listGalleryAttributes')->name('admin.{adapter}.get-gallery-attribute');
            Route::get('get-{adapter}-credentials', 'list{Adapter}Credential')->name('{adapter}.credential.fetch-all');
            Route::get('get-{adapter}-channel', 'listChannel')->name('{adapter}.channel.fetch-all');
            Route::get('get-{adapter}-currency', 'listCurrency')->name('{adapter}.currency.fetch-all');
            Route::get('get-{adapter}-locale', 'listLocale')->name('{adapter}.locale.fetch-all');
            Route::get('get-{adapter}-attrGroup', 'listAttributeGroup')->name('{adapter}.attribute-group.fetch-all');
            Route::get('get-{adapter}-family', 'list{Adapter}Family')->name('admin.{adapter}.get-all-family-variants');
        });

    });
});
```

---

## STEP 8: Create Translation File

### Template: `src/Resources/lang/en_US/app.php`
```php
<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                '{adapter}'         => '{AdapterTitle}',
                'credentials'       => 'Credentials',
                'export-mappings'   => 'Export Mappings',
                'import-mappings'   => 'Import Mappings',
                'settings'          => 'Settings',
            ],
        ],
    ],
    '{adapter}' => [
        'acl' => [
            'credential' => [
                'create' => 'Create Credential',
                'edit'   => 'Edit Credential',
                'delete' => 'Delete Credential',
            ],
        ],
        'credential' => [
            'created'         => 'Credential created successfully',
            'updated'         => 'Credential updated successfully',
            'deleted'         => 'Credential deleted successfully',
            'invalid'         => 'Invalid credentials',
            'already_taken'   => 'Credentials already exist',
        ],
    ],
];
```

---

## STEP 9: Update Existing Adapter to Use Product Mapping Table

### Template: Update `src/Adapters/{Adapter}Adapter.php`

Find the `syncProduct` method and replace generic `ProductChannelMapping` usage with adapter-specific table:

```php
use Webkul\{Adapter}\Models\{Adapter}ProductMapping;

public function syncProduct(Product $product, array $localeMappedData): SyncResult
{
    try {
        $this->ensureValidToken();

        // Use adapter-specific mapping table instead of generic one
        $existingMapping = {Adapter}ProductMapping::where('product_id', $product->id)
            ->where('connector_id', $this->connectorId)
            ->first();

        $existingExternalId = $existingMapping?->external_id;

        // ... rest of sync logic

        // Update or create mapping
        {Adapter}ProductMapping::updateOrCreate(
            [
                'product_id' => $product->id,
                'connector_id' => $this->connectorId,
            ],
            [
                'external_id' => $externalProductId,
                'external_sku' => $localeMappedData['sku'] ?? null,
                'variant_data' => $variantData ?? [],
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]
        );

        return new SyncResult(
            success: true,
            externalId: $externalProductId,
            action: $existingExternalId ? 'updated' : 'created'
        );
    } catch (\Exception $e) {
        // Log error and update mapping
        {Adapter}ProductMapping::updateOrCreate(
            [
                'product_id' => $product->id,
                'connector_id' => $this->connectorId,
            ],
            [
                'sync_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]
        );

        return new SyncResult(
            success: false,
            action: 'failed',
            errors: [$e->getMessage()]
        );
    }
}
```

---

## STEP 10: Run Migrations & Test

```bash
# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan cache:clear

# Test in browser
# Navigate to: /admin/{adapter}/credentials
```

---

## Quick Reference: Platform-Specific Customizations

### Amazon SP-API
- **Credentials:** `seller_id`, `marketplace_id`, `region`, `sp_api_refresh_token`, `lwa_access_token`, `lwa_refresh_interval`
- **Special Features:** Multi-region support, LWA OAuth2, marketplace selection
- **API Docs:** https://developer-docs.amazon.com/sp-api/

### eBay
- **Credentials:** `oauth_token`, `oauth_token_secret`, `site_id` (eBay.com, eBay.co.uk, etc.), `user_token`
- **Special Features:** OAuth 2.0 flow, category mapping, shipping policies
- **API Docs:** https://developer.ebay.com/api-docs/static/rest-home.html

### Noon
- **Credentials:** `partner_id`, `api_key`, `marketplace_code` (UAE, KSA, EGY)
- **Special Features:** Dual-header auth (x-partner-id + Authorization), Arabic support
- **API Docs:** https://developer.noon.partners/

### WooCommerce
- **Credentials:** `store_url`, `consumer_key`, `consumer_secret`, `version` (v3)
- **Special Features:** Self-hosted, REST API v3, webhook support
- **API Docs:** https://woocommerce.github.io/woocommerce-rest-api-docs/

### Magento 2
- **Credentials:** `base_url`, `admin_token`, `store_code`, `store_view_mapping`
- **Special Features:** Admin token auth, store view mapping, REST API V1
- **API Docs:** https://developer.adobe.com/commerce/webapi/rest/

### EasyOrders
- **Credentials:** `merchant_id`, `api_key`, `commission_rate`, `webhook_secret`
- **Special Features:** Commission tracking, RESTful API
- **API Docs:** [Platform specific]

---

## Verification Checklist

After implementing each adapter, verify:

- [ ] All 30 files created
- [ ] Migrations run successfully (MySQL + PostgreSQL + SQLite)
- [ ] Routes registered (check `php artisan route:list | grep {adapter}`)
- [ ] Menu appears in admin sidebar
- [ ] ACL permissions work (test with restricted user)
- [ ] Credential CRUD operations work
- [ ] Connection test successful
- [ ] Product sync creates adapter-specific mapping records
- [ ] Dark mode supported in all views
- [ ] No PSR-12 violations (`./vendor/bin/pint --test`)

---

## Next Steps

1. Implement remaining 6 adapters using these templates
2. Add E2E Playwright tests (see `docs/e2e-test-template.md`)
3. Implement rate-limit metrics dashboard (see `docs/rate-limit-dashboard.md`)
4. Run full test suite: `./vendor/bin/pest --parallel`

---

**Estimated Total Time:** 30-40 hours for all 6 remaining adapters (using templates)

**Priority Order:**
1. Amazon (highest demand)
2. WooCommerce (widely used)
3. eBay (established marketplace)
4. Magento2 (enterprise users)
5. Noon (regional expansion)
6. EasyOrders (niche market)
