# Models & Repositories — UnoPim

---

## Eloquent Models

### Base Classes

| Base | When to use |
|---|---|
| `Illuminate\Database\Eloquent\Model` | Standard models |
| `Webkul\Core\Eloquent\TranslatableModel` | Models with translations |
| Implement `HistoryAuditable` + use `HistoryTrait` | Models needing audit trail |

### Concord Proxy Pattern

Every model must have three parts:

```php
// 1. Contract (interface) — Contracts/Example.php
namespace Webkul\Example\Contracts;
interface Example {}

// 2. Model (implementation) — Models/Example.php
namespace Webkul\Example\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Example\Contracts\Example as ExampleContract;

class Example extends Model implements ExampleContract
{
    protected $fillable = ['code', 'name', 'status'];

    protected $casts = [
        'additional_data' => 'array',
        'status'          => 'boolean',
    ];
}

// 3. Proxy — Models/ExampleProxy.php
namespace Webkul\Example\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ExampleProxy extends ModelProxy {}
```

### Model with Audit Trail

```php
use OwenIt\Auditing\Contracts\Auditable as HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Product extends Model implements ProductContract, HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['product'];
    protected $auditExclude = ['id', 'created_at', 'updated_at'];
}
```

### JSON Casts

Product/Category models use JSON casts for flexible value storage:

```php
protected $casts = [
    'values'          => 'array',    // Structured JSON values
    'additional_data' => 'array',
];
```

---

## Repository Pattern

All repositories extend `Webkul\Core\Eloquent\Repository`:

```php
namespace Webkul\Example\Repositories;

use Webkul\Core\Eloquent\Repository;

class ExampleRepository extends Repository
{
    /**
     * Specify the model class name.
     */
    public function model(): string
    {
        return \Webkul\Example\Contracts\Example::class;
    }

    /**
     * Create a new example.
     */
    public function create(array $data): Example
    {
        Event::dispatch('example.create.before', $data);

        $example = parent::create($data);

        Event::dispatch('example.create.after', $example);

        return $example;
    }
}
```

### Available Repository Methods

| Method | Description |
|---|---|
| `create(array $data)` | Create new record |
| `update(array $data, $id)` | Update existing record |
| `find($id)` | Find by primary key |
| `findOrFail($id)` | Find or throw 404 |
| `findWhere(array $conditions)` | Find by conditions |
| `delete($id)` | Delete record |
| `all()` | Get all records |
| `paginate($limit)` | Paginated results |

### Injecting Repositories

Use constructor injection:

```php
public function __construct(
    protected ProductRepository $productRepository,
    protected AttributeRepository $attributeRepository
) {}
```

---

## Key Domain Models

### Product

| Field | Type | Notes |
|---|---|---|
| `sku` | string | Unique identifier |
| `type` | string | `simple` or `configurable` |
| `attribute_family_id` | int | FK to attribute families |
| `parent_id` | int|null | For configurable variants |
| `values` | JSON | Structured scoped values |

### Attribute

| Field | Type | Notes |
|---|---|---|
| `code` | string | Unique code |
| `type` | string | `text`, `select`, `boolean`, `price`, etc. |
| `is_required` | boolean | Required flag |
| `is_unique` | boolean | Uniqueness constraint |
| `value_per_locale` | boolean | Locale-scoped values |
| `value_per_channel` | boolean | Channel-scoped values |
| `is_filterable` | boolean | Show in filters |

### Category

Uses nested set via `Kalnoy\Nestedset\NodeTrait`:

| Field | Type | Notes |
|---|---|---|
| `code` | string | Unique code |
| `parent_id` | int|null | Parent category |
| `additional_data` | JSON | Extra data |

---

## Migrations

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examples', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->json('additional_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examples');
    }
};
```

Place migrations in `packages/Webkul/Example/src/Database/Migrations/`.
Load in ServiceProvider: `$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');`
