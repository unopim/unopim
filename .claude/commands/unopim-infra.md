# UnoPim INFRASTRUCTURE Layer Skill

Use this skill when working with Concord modules, service providers, DataGrid, events, history/auditing, theme/assets, or the build pipeline.

## Concord Module Registration

**Config:** `config/concord.php` registers all modules:

```php
// Every package has: Webkul\{Package}\Providers\ModuleServiceProvider
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\{Package}\Models\MyModel::class,
    ];
}
```

**Base:** `Webkul\Core\Providers\CoreModuleServiceProvider` extends `Konekt\Concord\BaseModuleServiceProvider`
- Auto-registers migrations, models, enums, views, routes on boot

## ServiceProvider Pattern

Each package has a main ServiceProvider:

```php
class {Package}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', '{package}');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', '{package}');
        $this->publishes([...]);
        $this->app->register(EventServiceProvider::class);
    }

    public function register(): void
    {
        // Bind singletons, facades, commands
    }
}
```

**Core facades:** `core` (Core helper), `elasticsearch` (ElasticSearch client)
**DB macro:** `DB::rawQueryGrammar()` returns GrammarQueryManager instance

## DataGrid Pattern

Create a DataGrid by extending the abstract base:

```php
use Webkul\DataGrid\DataGrid;

class MyDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';
    protected $sortColumn = 'created_at';
    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $this->setQueryBuilder(
            DB::table('my_table')->addSelect('id', 'name', 'status')
        );
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.my.name'),
            'type'       => 'string',       // string|integer|boolean|dropdown|date_range|datetime_range|price
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-edit',
            'title'  => trans('admin::app.edit'),
            'method' => 'GET',
            'url'    => function ($row) { return route('admin.my.edit', $row->id); },
        ]);
    }

    public function prepareMassActions()
    {
        $this->addMassAction([
            'title'  => trans('admin::app.delete'),
            'method' => 'POST',
            'url'    => route('admin.my.mass_delete'),
        ]);
    }
}
```

**For export:** implement `ExportableInterface` and add `use AttributeColumnTrait;`
**Column types:** `ColumnTypeEnum` - STRING, INTEGER, BOOLEAN, DROPDOWN, DATE_RANGE, DATE_TIME_RANGE, PRICE

## Event System

Register listeners in `EventServiceProvider`:

```php
class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        'catalog.product.create.after' => ['App\Listeners\MyListener@handle'],
    ];
}
```

**Event naming convention:** `{domain}.{entity}.{action}.{timing}`
- Examples: `catalog.product.create.before`, `catalog.product.update.after`
- FPC listens to: `catalog.product.update.after`, `catalog.category.delete.before`
- Notifications: `data_transfer.export.completed`, `data_transfer.imports.completed`
- Webhooks: `catalog.product.create.after`, `catalog.product.update.after`

**Dispatching:** `Event::dispatch('catalog.product.create.before');`

## History / Auditing

Enable versioning on a model:

```php
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class MyModel extends Model implements HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['my_entity'];
    protected $historyFields = ['name', 'status'];
    protected $historyProxyFields = ['related_items'];     // For pivot changes
    protected $historyTranslatableFields = ['name'];       // For i18n

    public function getPrimaryModelIdForHistory(): int { return $this->id; }
}
```

**Interface:** `HistoryAuditable extends OwenIt\Auditing\Contracts\Auditable`

## Theme & Build Pipeline

**Vite config:** `packages/Webkul/Admin/vite.config.js`
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({ input: 'src/Resources/assets/js/app.js', refresh: true })],
});
```

**Blade directive:** `@unoPimVite` for asset loading
**Theme:** `ThemeServiceProvider` registers `ViewRenderEventManager`, `ThemeViewFinder`
**Events:** `unopim.shop.layout.body.after` for injecting templates

## Repository Config

**File:** `config/repository.php`
- Configures cache TTL, driver, skip criteria for Prettus repositories

## Key Rules

- ALWAYS extend `CoreModuleServiceProvider` for new package modules
- ALWAYS register EventServiceProvider inside the main ServiceProvider's `boot()`
- DataGrids MUST implement `prepareQueryBuilder()` and `prepareColumns()`
- Event names follow `{domain}.{entity}.{action}.{before|after}` convention
- History tracking requires BOTH `HistoryAuditable` interface AND `HistoryTrait`
- Config merging: use `$this->mergeConfigFrom()` in `register()`, `$this->publishes()` in `boot()`
- Build: run `npm run build` from the Admin package directory
