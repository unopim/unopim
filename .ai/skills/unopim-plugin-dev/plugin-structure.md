# Plugin Structure — UnoPim

---

## Directory Layout

```
packages/Webkul/Example/
└── src/
    ├── Config/
    │   ├── menu.php           # Admin sidebar menu items
    │   ├── acl.php            # Access control permissions
    │   ├── importer.php       # Custom importer definition (optional)
    │   └── exporter.php       # Custom exporter definition (optional)
    ├── Contracts/
    │   └── Example.php        # Model interface
    ├── Database/
    │   ├── Migrations/        # Database migrations
    │   └── Seeders/           # Database seeders (optional)
    ├── Helpers/
    │   ├── Importers/         # Custom importer classes (optional)
    │   └── Exporters/         # Custom exporter classes (optional)
    ├── Http/
    │   └── Controllers/       # HTTP controllers
    ├── Models/
    │   ├── Example.php        # Eloquent model
    │   └── ExampleProxy.php   # Concord proxy model
    ├── Providers/
    │   ├── ExampleServiceProvider.php
    │   └── ModuleServiceProvider.php
    ├── Repositories/
    │   └── ExampleRepository.php
    ├── Resources/
    │   ├── views/             # Blade templates
    │   ├── lang/              # Translation files
    │   └── assets/            # CSS/JS (optional)
    ├── Routes/
    │   └── routes.php         # Route definitions
    └── Validators/            # Custom validators (optional)
        └── JobInstances/
            ├── Import/
            └── Export/
```

---

## Minimal Service Provider

```php
<?php

namespace Webkul\Example\Providers;

use Illuminate\Support\ServiceProvider;

class ExampleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/routes.php');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'example');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'example');

        $this->publishes([
            __DIR__ . '/../../publishable/assets' => public_path('themes/default/build/example'),
        ], 'example-assets');
    }
}
```

---

## Module Service Provider (Concord)

```php
<?php

namespace Webkul\Example\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\Example\Models\Example::class,
    ];
}
```

Register in `config/concord.php`:

```php
'modules' => [
    \Webkul\Example\Providers\ModuleServiceProvider::class,
],
```

---

## Registration Steps

1. Add PSR-4 autoload to root `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Webkul\\Example\\": "packages/Webkul/Example/src"
        }
    }
}
```

2. Register ServiceProvider in `config/app.php`:

```php
'providers' => [
    // ...
    Webkul\Example\Providers\ExampleServiceProvider::class,
],
```

3. Register ModuleServiceProvider in `config/concord.php`:

```php
'modules' => [
    // ...
    \Webkul\Example\Providers\ModuleServiceProvider::class,
],
```

4. Run:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
```

---

## Deployment

```bash
# Build assets (if plugin has frontend)
npm run build

# Publish assets
php artisan vendor:publish --tag=example-assets --force

# Clear all caches
php artisan optimize:clear
```
