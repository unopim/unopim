# Events & Listeners — UnoPim

---

## Event Naming Convention

Events follow the pattern: `{domain}.{entity}.{action}.{before|after}`

```php
// Before action
Event::dispatch('catalog.product.create.before', $data);

// After action
Event::dispatch('catalog.product.create.after', $product);
```

### Common Events

| Event | When Fired |
|---|---|
| `catalog.product.create.before/after` | Product creation |
| `catalog.product.update.before/after` | Product update |
| `catalog.product.delete.before/after` | Product deletion |
| `catalog.category.create.before/after` | Category creation |
| `catalog.category.update.before/after` | Category update |
| `catalog.category.delete.before/after` | Category deletion |
| `catalog.attribute.create.before/after` | Attribute creation |
| `catalog.attribute_family.create.before/after` | Family creation |
| `data_transfer.imports.import.now.before/after` | Import execution |

---

## Listening to Events

### In EventServiceProvider

```php
namespace Webkul\Example\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'catalog.product.create.after' => [
            \Webkul\Example\Listeners\ProductListener::class . '@handleProductCreated',
        ],
    ];
}
```

### Inline Listener

```php
Event::listen('catalog.product.create.after', function ($product) {
    // Handle event
});
```

---

## Creating a Listener

```php
namespace Webkul\Example\Listeners;

class ProductListener
{
    /**
     * Handle product created event.
     */
    public function handleProductCreated($product): void
    {
        // React to product creation
    }

    /**
     * Handle product updated event.
     */
    public function handleProductUpdated($product): void
    {
        // React to product update
    }
}
```

---

## Observers

Model observers are used for Eloquent lifecycle events:

```php
namespace Webkul\Example\Observers;

class ExampleObserver
{
    public function creating($model): void { /* Before creating */ }
    public function created($model): void { /* After creating */ }
    public function updating($model): void { /* Before updating */ }
    public function updated($model): void { /* After updating */ }
    public function deleting($model): void { /* Before deleting */ }
    public function deleted($model): void { /* After deleting */ }
}
```

Register in ServiceProvider:

```php
public function boot(): void
{
    \Webkul\Example\Models\Example::observe(
        \Webkul\Example\Observers\ExampleObserver::class
    );
}
```

---

## View Render Events

Inject content into Blade templates without modifying them:

```blade
{{-- In the template --}}
{!! view_render_event('unopim.admin.catalog.product.edit.before') !!}
```

```php
// Listen and inject custom view
Event::listen('unopim.admin.catalog.product.edit.before', function ($viewRenderEventManager) {
    $viewRenderEventManager->addTemplate('example::partials.custom-section');
});
```
