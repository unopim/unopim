# Architecture Patterns — UnoPim

UnoPim is a modular Laravel application using **Concord** for package management. All domain code lives in `packages/Webkul/`.

---

## Package Structure

```
packages/Webkul/
├── Admin/          # Admin panel: Controllers, DataGrids, Views, Config (acl/menu/system)
├── AdminApi/       # REST API: Controllers, Routes (v1/rest), OAuth
├── Attribute/      # Attribute system: Models, Repositories, Enums, Rules
├── Category/       # Category tree (nested-set): Models, Repositories
├── Completeness/   # Product completeness scoring
├── Core/           # Foundation: Models, Helpers, Eloquent base, Facades
├── DataGrid/       # Abstract DataGrid engine
├── DataTransfer/   # Import/Export pipeline
├── ElasticSearch/  # ES indexing and query builders
├── HistoryControl/ # Audit trail
├── Installer/      # Installation wizard
├── MagicAI/        # AI content generation
├── Notification/   # Event-driven notifications
├── Product/        # Product domain (type strategy)
├── Theme/          # Theme engine
├── User/           # Admin users & roles
└── Webhook/        # Outgoing webhooks
```

---

## Core Patterns

### 1. Concord Proxy Models

Every package uses proxy models to allow model swapping without modifying core code:

```php
// Contract (interface)
namespace Webkul\Product\Contracts;
interface Product {}

// Model (implementation)
namespace Webkul\Product\Models;
class Product extends Model implements ProductContract { ... }

// Proxy (swappable reference)
namespace Webkul\Product\Models;
class ProductProxy extends ModelProxy implements ProductContract
{
    use ProxiesContract;
}
```

**Register in ModuleServiceProvider:**

```php
$this->app->concord->registerModel(
    \Webkul\Product\Contracts\Product::class,
    \Webkul\Product\Models\Product::class
);
```

**Override in any package:**

```php
$this->app->concord->registerModel(
    \Webkul\Product\Contracts\Product::class,
    \App\Models\CustomProduct::class
);
```

### 2. Repository Pattern

All repositories extend `Webkul\Core\Eloquent\Repository`:

```php
namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProductRepository extends Repository
{
    public function model(): string
    {
        return \Webkul\Product\Contracts\Product::class;
    }
}
```

Key repository methods: `create()`, `update()`, `find()`, `findOrFail()`, `delete()`, `all()`, `findWhere()`, `paginate()`.

### 3. Strategy Pattern (Product Types)

```
AbstractType (base)
├── Simple      — Standard product
└── Configurable — Product with variants (super_attributes)
```

Configured in `Product/src/Config/product_types.php`. Resolved via `config('product_types.{type}.class')`.

### 4. Event-Driven Architecture

Events fired before/after CRUD on all entities:

```php
Event::dispatch('catalog.product.create.before', $data);
// ... perform action ...
Event::dispatch('catalog.product.create.after', $product);
```

### 5. State Machine (DataTransfer)

```
PENDING → VALIDATED → PROCESSING → PROCESSED → LINKING → LINKED → INDEXING → INDEXED → COMPLETED
```

### 6. Nested Set (Categories)

Uses `Kalnoy\Nestedset\NodeTrait` — provides `parent()`, `children()`, `ancestors()`, `descendants()`.

### 7. Structured JSON Values

Product/Category values stored as JSON with scoping:

```json
{
    "common": { "sku": "...", "name": "..." },
    "locale_specific": { "en_US": {}, "fr_FR": {} },
    "channel_specific": { "default": {} },
    "channel_locale_specific": { "default": { "en_US": {} } },
    "categories": ["cat_code_1"],
    "associations": {
        "related_products": ["SKU-1"],
        "up_sells": ["SKU-2"],
        "cross_sells": ["SKU-3"]
    }
}
```

### 8. Facade Pattern

Core facades: `Core`, `ElasticSearch`, `ProductImage`, `MagicAI`.

### 9. View Render Events

Inject content into templates without modifying them:

```blade
{!! view_render_event('unopim.admin.layout.content.before') !!}
```

### 10. Config Merge Pattern

Plugins register via `mergeConfigFrom()`:

```php
$this->mergeConfigFrom(__DIR__ . '/../Config/menu.php', 'menu.admin');
$this->mergeConfigFrom(__DIR__ . '/../Config/acl.php', 'acl');
```

---

## Service Provider Lifecycle

1. `register()` — Bind services, merge configs
2. `boot()` — Load routes, migrations, translations, views, publish assets

---

## Key Facades & Helpers

| Helper/Facade | Usage |
|---|---|
| `core()` | `getAllChannels()`, `getCurrentLocale()`, `getConfigData()` |
| `Core` facade | Same methods via facade |
| `ElasticSearch` facade | Search indexing and queries |
| `bouncer()` | ACL permission checks |
