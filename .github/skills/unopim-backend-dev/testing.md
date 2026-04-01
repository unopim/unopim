# Testing — UnoPim

UnoPim uses **Pest** (built on PHPUnit) for testing.

---

## Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest --testsuite="Admin Feature Test"
./vendor/bin/pest --testsuite="Api Feature Test"
./vendor/bin/pest --testsuite="Core Unit Test"

# Run specific test file
./vendor/bin/pest packages/Webkul/Admin/tests/Feature/Catalog/ProductTest.php

# Run specific test by name
./vendor/bin/pest --filter="it_can_create_a_product"

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/pest --parallel
```

## Test Suites

| Suite | Directory |
|---|---|
| Admin Feature Test | `packages/Webkul/Admin/tests/Feature` |
| Api Feature Test | `packages/Webkul/AdminApi/tests/Feature` |
| Core Unit Test | `packages/Webkul/Core/tests/Unit` |
| DataGrid Unit Test | `packages/Webkul/DataGrid/tests/Unit` |
| DataTransfer Unit Test | `packages/Webkul/DataTransfer/tests/Unit` |
| User Feature Test | `packages/Webkul/User/tests/Feature` |
| Installer Feature Test | `packages/Webkul/Installer/tests/Feature` |
| ElasticSearch Feature Test | `packages/Webkul/ElasticSearch/tests/Feature` |
| Completeness Feature Test | `packages/Webkul/Completeness/tests/Feature` |

## Test Environment

PHPUnit is configured with:

- `APP_ENV=testing`
- `CACHE_DRIVER=array`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`
- `MAIL_MAILER=array`

---

## Writing Feature Tests

### Admin Feature Test

```php
<?php

use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

describe('Product CRUD', function () {
    it('can list products', function () {
        $this->get(route('admin.catalog.products.index'))
            ->assertStatus(200);
    });

    it('can create a product', function () {
        $data = [
            'sku'                 => 'test-product',
            'type'                => 'simple',
            'attribute_family_id' => 1,
        ];

        $this->post(route('admin.catalog.products.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'test-product']);
    });

    it('can update a product', function () {
        $product = Product::factory()->create();

        $this->put(
            route('admin.catalog.products.update', $product->id),
            ['sku' => 'updated-sku']
        )->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'updated-sku']);
    });

    it('can delete a product', function () {
        $product = Product::factory()->create();

        $this->delete(route('admin.catalog.products.delete', $product->id))
            ->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });
});
```

### API Feature Test

```php
<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

describe('Product API', function () {
    it('can list products via API', function () {
        $this->withHeaders($this->headers)
            ->get(route('admin.api.products.index'))
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['sku', 'type', 'values'],
                ],
            ]);
    });

    it('can create a product via API', function () {
        $this->withHeaders($this->headers)
            ->post(route('admin.api.products.store'), [
                'sku'                 => 'api-test',
                'type'                => 'simple',
                'attribute_family_id' => 1,
            ])
            ->assertStatus(201);
    });
});
```

---

## Model Factories

Located in each package under `Database/Factories/`:

```php
namespace Webkul\Example\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Example\Models\Example;

class ExampleFactory extends Factory
{
    protected $model = Example::class;

    public function definition(): array
    {
        return [
            'code'   => $this->faker->unique()->slug(2),
            'name'   => $this->faker->words(3, true),
            'status' => $this->faker->boolean(80),
        ];
    }
}
```

---

## Common Assertions

Prefer specific assertions over generic `assertStatus()`:

| Use | Instead of |
|---|---|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertCreated()` | `assertStatus(201)` |
| `assertNoContent()` | `assertStatus(204)` |
| `assertRedirect()` | `assertStatus(302)` |
| `assertForbidden()` | `assertStatus(403)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertUnprocessable()` | `assertStatus(422)` |
| `assertDatabaseHas('table', [...])` | Manual DB queries |
| `assertDatabaseMissing('table', [...])` | Manual DB queries |
| `assertJsonStructure([...])` | Manual JSON parsing |

---

## Test Naming

Use descriptive `it()` blocks:

```php
it('can create a product with valid data')
it('cannot create a product without sku')
it('returns 403 when user lacks permission')
it('can bulk delete selected products')
```

Group related tests with `describe()`:

```php
describe('Product listing', function () { ... });
describe('Product creation', function () { ... });
describe('Product ACL', function () { ... });
```

---

## Architecture Testing

Pest supports architecture testing to enforce code conventions:

```php
arch('models should extend Model')
    ->expect('Webkul\Product\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('repositories extend base repository')
    ->expect('Webkul\Product\Repositories')
    ->toExtend('Webkul\Core\Eloquent\Repository');

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('controllers have correct suffix')
    ->expect('Webkul\Admin\Http\Controllers')
    ->toHaveSuffix('Controller');
```

---

## Adding Tests to a New Package

If you add tests to a new package, you need to:

1. **Register in `tests/Pest.php`:**

```php
uses(Webkul\NewPackage\Tests\NewPackageTestCase::class)
    ->in('../packages/Webkul/NewPackage/tests');
```

2. **Register in `composer.json` (autoload-dev):**

```json
"autoload-dev": {
    "psr-4": {
        "Webkul\\NewPackage\\Tests\\": "packages/Webkul/NewPackage/tests"
    }
}
```

3. **Register in `phpunit.xml`:**

```xml
<testsuite name="NewPackage Feature Test">
    <directory suffix="Test.php">packages/Webkul/NewPackage/tests/Feature</directory>
</testsuite>
```

4. **Run composer dump-autoload:**

```bash
composer dump-autoload
```

---

## Common Pitfalls

- Forgetting to run `composer dump-autoload` after adding test namespace
- Not registering test case in `tests/Pest.php`
- Not adding testsuite to `phpunit.xml` for package-specific testing
- Deleting tests without approval
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Not using factories for model creation in tests
- Forgetting `beforeEach(fn() => $this->loginAsAdmin())` for admin tests
