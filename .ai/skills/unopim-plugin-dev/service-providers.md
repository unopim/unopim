# Service Providers — UnoPim Plugins

---

## ServiceProvider Lifecycle

### `register()` — Bind and merge

```php
public function register(): void
{
    // Merge admin menu items
    $this->mergeConfigFrom(dirname(__DIR__) . '/Config/menu.php', 'menu.admin');

    // Merge ACL permissions
    $this->mergeConfigFrom(dirname(__DIR__) . '/Config/acl.php', 'acl');

    // Merge custom importers
    $this->mergeConfigFrom(dirname(__DIR__) . '/Config/importer.php', 'importers');

    // Merge custom exporters
    $this->mergeConfigFrom(dirname(__DIR__) . '/Config/exporter.php', 'exporters');

    // Merge system configuration
    $this->mergeConfigFrom(dirname(__DIR__) . '/Config/system.php', 'core');
}
```

### `boot()` — Load resources

```php
public function boot(): void
{
    // Load routes
    $this->loadRoutesFrom(__DIR__ . '/../Routes/routes.php');

    // Load database migrations
    $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

    // Load translations (namespace: 'example')
    $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'example');

    // Load Blade views (namespace: 'example')
    $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'example');

    // Publish assets
    $this->publishes([
        __DIR__ . '/../../publishable/assets' => public_path('themes/default/build/example'),
    ], 'example-assets');

    // Register observers
    \Webkul\Example\Models\Example::observe(
        \Webkul\Example\Observers\ExampleObserver::class
    );
}
```

---

## Event Service Provider

```php
namespace Webkul\Example\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'catalog.product.create.after' => [
            \Webkul\Example\Listeners\ProductListener::class . '@handleCreated',
        ],
        'catalog.product.update.after' => [
            \Webkul\Example\Listeners\ProductListener::class . '@handleUpdated',
        ],
    ];
}
```

Register in main ServiceProvider:

```php
public function register(): void
{
    $this->app->register(EventServiceProvider::class);
}
```

---

## Config Merge Points

| Config Key | What It Controls | File |
|---|---|---|
| `menu.admin` | Admin sidebar menu items | `Config/menu.php` |
| `acl` | Permission tree (ACL) | `Config/acl.php` |
| `core` | System configuration fields | `Config/system.php` |
| `importers` | Import profile definitions | `Config/importer.php` |
| `exporters` | Export profile definitions | `Config/exporter.php` |
| `quick_exporters` | One-click export definitions | `Config/quick_exporters.php` |
