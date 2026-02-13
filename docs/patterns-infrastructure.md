# UnoPim - INFRASTRUCTURE Layer Patterns & Skills

> Reference documentation for the Infrastructure architectural layer.
> Generated from exhaustive codebase scan - 2026-02-08

---

## Table of Contents

1. [Konekt Concord Module System](#1-konekt-concord-module-system)
2. [Core Service Provider Infrastructure](#2-core-service-provider-infrastructure)
3. [DataGrid Infrastructure](#3-datagrid-infrastructure)
4. [Event System Infrastructure](#4-event-system-infrastructure)
5. [History/Versioning (Auditing) Infrastructure](#5-historyversioning-auditing-infrastructure)
6. [Full Page Cache (FPC) Infrastructure](#6-full-page-cache-fpc-infrastructure)
7. [Notification Infrastructure](#7-notification-infrastructure)
8. [Webhook Infrastructure](#8-webhook-infrastructure)
9. [Completeness Infrastructure](#9-completeness-infrastructure)
10. [Product & Attribute Service Providers](#10-product--attribute-service-providers)
11. [Admin Service Provider Infrastructure](#11-admin-service-provider-infrastructure)
12. [Theme Infrastructure](#12-theme-infrastructure)
13. [Database Grammar System](#13-database-grammar-system)
14. [Repository Pattern Configuration](#14-repository-pattern-configuration)
15. [Build & Asset Pipeline](#15-build--asset-pipeline)
16. [Core Configuration Patterns](#16-core-configuration-patterns)
17. [Key Inheritance Chains](#17-key-inheritance-chains)
18. [Configuration Merging Patterns](#18-configuration-merging-patterns)
19. [Request Validation & Filtering Patterns](#19-request-validation--filtering-patterns)
20. [Lazy Loading & Deferred Registration](#20-lazy-loading--deferred-registration)

---

## 1. Konekt Concord Module System

### Module Registration & Convention

**File:** `/config/concord.php`

```php
// Convention class: Webkul\Core\CoreConvention
// Registered modules (as of current snapshot):
- Webkul\Admin\Providers\ModuleServiceProvider
- Webkul\Attribute\Providers\ModuleServiceProvider
- Webkul\Category\Providers\ModuleServiceProvider
- Webkul\Core\Providers\ModuleServiceProvider
- Webkul\DataTransfer\Providers\ModuleServiceProvider
- Webkul\HistoryControl\Providers\ModuleServiceProvider
- Webkul\Notification\Providers\ModuleServiceProvider
- Webkul\Product\Providers\ModuleServiceProvider
- Webkul\User\Providers\ModuleServiceProvider
- Webkul\MagicAI\Providers\ModuleServiceProvider
```

### Base Module Service Provider Pattern

**Class:** `Webkul\Core\Providers\CoreModuleServiceProvider extends Konekt\Concord\BaseModuleServiceProvider`

**Key Methods:**

```php
public function boot(): void {
    if ($this->areMigrationsEnabled()) {
        $this->registerMigrations();
    }
    if ($this->areModelsEnabled()) {
        $this->registerModels();
        $this->registerEnums();
        $this->registerRequestTypes();
    }
    if ($this->areViewsEnabled()) {
        $this->registerViews();
    }
    if ($routes = $this->config('routes', true)) {
        $this->registerRoutes($routes);
    }
}
```

### Module Service Provider Pattern

Each module has a `ModuleServiceProvider` extending `CoreModuleServiceProvider`:

```php
// Example: Webkul\Core\Providers\ModuleServiceProvider
class ModuleServiceProvider extends CoreModuleServiceProvider {
    protected $models = [
        \Webkul\Core\Models\Channel::class,
        \Webkul\Core\Models\CoreConfig::class,
        \Webkul\Core\Models\Currency::class,
        \Webkul\Core\Models\Locale::class,
    ];
}

// Example: Webkul\HistoryControl\Providers\ModuleServiceProvider
class ModuleServiceProvider extends CoreModuleServiceProvider {
    protected $models = [
        \Webkul\HistoryControl\Models\History::class,
    ];
}
```

---

## 2. Core Service Provider Infrastructure

**Class:** `Webkul\Core\Providers\CoreServiceProvider extends Illuminate\Support\ServiceProvider`

### Bootstrap Phase (boot method)

```php
public function boot(): void {
    // 1. Load helpers
    include __DIR__.'/../Http/helpers.php';

    // 2. Load migrations
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

    // 3. Load translations
    $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'core');

    // 4. Publish configs
    $this->publishes([
        dirname(__DIR__).'/Config/concord.php'       => config_path('concord.php'),
        dirname(__DIR__).'/Config/repository.php'    => config_path('repository.php'),
        dirname(__DIR__).'/Config/visitor.php'       => config_path('visitor.php'),
        dirname(__DIR__).'/Config/elasticsearch.php' => config_path('elasticsearch.php'),
    ]);

    // 5. Register child providers
    $this->app->register(EventServiceProvider::class);
    $this->app->register(VisitorServiceProvider::class);

    // 6. Bind exception handler
    $this->app->bind(ExceptionHandler::class, Handler::class);

    // 7. Load views
    $this->loadViewsFrom(__DIR__.'/../Resources/views', 'core');

    // 8. Register event listeners
    Event::listen('unopim.shop.layout.body.after', static function (ViewRenderEventManager $vm) {
        $vm->addTemplate('core::blade.tracer.style');
    });

    // 9. Override Artisan commands
    $this->app->extend('command.down', fn() => new \Webkul\Core\Console\Commands\DownCommand);
    $this->app->extend('command.up', fn() => new \Webkul\Core\Console\Commands\UpCommand);

    // 10. Register image cache route
    if (is_string(config('imagecache.route'))) {
        $this->app['router']->get(config('imagecache.route').'/{template}/{filename}', [
            'uses' => 'Webkul\Core\ImageCache\Controller@getResponse',
            'as'   => 'imagecache',
        ])->where(['filename' => '[ \w\\.\\/\\-\\@\(\)\=]+']);
    }

    // 11. Register database grammar macro
    DB::macro('rawQueryGrammar', fn() => GrammarQueryManager::getGrammar());
}

public function register(): void {
    $this->registerFacades();
    $this->registerCommands();
    $this->registerBladeCompiler();
}
```

### Facade Registration Pattern

```php
protected function registerFacades(): void {
    $loader = AliasLoader::getInstance();

    // Core Facade
    $loader->alias('core', CoreFacade::class);
    $this->app->singleton('core', fn() => app()->make(Core::class));

    // ElasticSearch Facade
    $this->app->singleton('elasticsearch', fn() => new ElasticSearch);
    $loader->alias('elasticsearch', ElasticSearchFacade::class);
    $this->app->singleton(ElasticSearchClient::class, fn() =>
        app()->make('elasticsearch')->connection()
    );
}

protected function registerCommands(): void {
    if ($this->app->runningInConsole()) {
        $this->commands([
            \Webkul\Core\Console\Commands\UnoPimPublish::class,
            \Webkul\Core\Console\Commands\UnoPimVersion::class,
        ]);
    }
}

protected function registerBladeCompiler(): void {
    $this->app->singleton('blade.compiler', function ($app) {
        return new BladeCompiler($app['files'], $app['config']['view.compiled']);
    });
}
```

---

## 3. DataGrid Infrastructure

### Base DataGrid Class

**Class:** `Webkul\DataGrid\DataGrid (abstract)`

**Key Properties:**

```php
protected $primaryColumn = 'id';
protected $sortColumn;
protected $sortOrder = 'desc';
protected $itemsPerPage = 10;
protected $columns = [];
protected $actions = [];
protected $massActions = [];
protected $queryBuilder;
protected $searchPlaceholder = 'admin::app.components.datagrid.toolbar.search.title';
protected LengthAwarePaginator $paginator;
protected bool $exportable = false;
protected mixed $exportFile = null;
```

**Abstract Methods (must implement):**

```php
abstract public function prepareQueryBuilder();
abstract public function prepareColumns();
```

**Concrete Methods:**

```php
public function prepareActions() {}
public function prepareMassActions() {}

public function addColumn(array $column): void {
    $this->columns[] = new Column(
        index: $column['index'],
        label: $column['label'],
        type: $column['type'],
        options: $column['options'] ?? null,
        searchable: $column['searchable'],
        filterable: $column['filterable'],
        sortable: $column['sortable'],
        closure: $column['closure'] ?? null,
    );
}

public function addAction(array $action): void {
    $this->actions[] = new Action(
        index: $action['index'] ?? '',
        icon: $action['icon'] ?? '',
        title: $action['title'],
        method: $action['method'],
        url: $action['url'],
        frontendView: $action['frontend_view'] ?? '',
    );
}

public function addMassAction(array $massAction): void {
    $this->massActions[] = new MassAction(
        icon: $massAction['icon'] ?? '',
        title: $massAction['title'],
        method: $massAction['method'],
        url: $massAction['url'],
        options: $massAction['options'] ?? [],
    );
}

public function addFilter(string $datagridColumn, mixed $queryColumn): void
public function setQueryBuilder($queryBuilder = null): void
public function validatedRequest(): array
public function processRequestedFilters(array $requestedFilters)
public function processRequestedSorting($requestedSort)
public function processRequestedPagination($requestedPagination): LengthAwarePaginator
public function processRequest(): void
public function prepare(): void
public function toJson()
```

**Request Validation Structure:**

```php
public function validatedRequest(): array {
    request()->validate([
        'filters'     => ['sometimes', 'required', 'array'],
        'sort'        => ['sometimes', 'required', 'array'],
        'pagination'  => ['sometimes', 'required', 'array'],
        'export'      => ['sometimes', 'required', 'boolean'],
        'format'      => ['sometimes', 'required', 'in:csv,xls,xlsx'],
        'productIds'  => ['sometimes', 'array'],
    ]);
    return request()->only(['filters', 'sort', 'pagination', 'export', 'format', 'productIds']);
}
```

### Filter Processing by Column Type

| Column Type | Filtering Behavior |
|---|---|
| `ColumnTypeEnum::STRING` | LIKE filtering with OR conditions |
| `ColumnTypeEnum::INTEGER` | Exact value matching |
| `ColumnTypeEnum::DROPDOWN` | Exact value matching |
| `ColumnTypeEnum::DATE_RANGE` | BETWEEN filtering with time boundaries (00:00:01 to 23:59:59) |
| `ColumnTypeEnum::DATE_TIME_RANGE` | BETWEEN filtering with exact timestamps |
| `ColumnTypeEnum::BOOLEAN` | DB grammar-specific boolean handling |

### Export Implementation

```php
if (isset($requestedParams['export']) && (bool) $requestedParams['export']) {
    $this->exportable = true;
    $gridData = $this instanceof ExportableInterface ?
        $this->getExportableData($requestedParams) :
        $this->queryBuilder->get();
    $this->setExportFile($gridData, $requestedParams['format']);
    return;
}
```

### Column Class

**Class:** `Webkul\DataGrid\Column`

```php
public function __construct(
    public string $index,
    public string $label,
    public string $type,
    public ?array $options = null,
    public bool $searchable = false,
    public bool $filterable = false,
    public bool $sortable = false,
    public mixed $closure = null,
) {
    $this->init();
}

public function init(): void {
    $this->setDatabaseColumnName();

    switch ($this->type) {
        case ColumnTypeEnum::BOOLEAN->value:
            $this->setFormOptions($this->getBooleanOptions());
            break;
        case ColumnTypeEnum::DROPDOWN->value:
        case ColumnTypeEnum::PRICE->value:
            $this->setFormOptions($this->options);
            break;
        case ColumnTypeEnum::DATE_RANGE->value:
            $this->setFormInputType(FormInputTypeEnum::DATE->value);
            $this->setFormOptions($this->getRangeOptions());
            break;
        case ColumnTypeEnum::DATE_TIME_RANGE->value:
            $this->setFormInputType(FormInputTypeEnum::DATE_TIME->value);
            $this->setFormOptions($this->getRangeOptions('Y-m-d H:i:s'));
            break;
    }
}

public function getBooleanOptions(): array {
    return [
        ['label' => trans('admin::app.components.datagrid.filters.boolean-options.true'), 'value' => 1],
        ['label' => trans('admin::app.components.datagrid.filters.boolean-options.false'), 'value' => 0],
    ];
}

public function getRangeOptions(string $format = 'Y-m-d'): array {
    // Returns 8 date range options:
    // TODAY, YESTERDAY, THIS_WEEK, THIS_MONTH, LAST_MONTH,
    // LAST_THREE_MONTHS, LAST_SIX_MONTHS, THIS_YEAR
}
```

### Action & MassAction Classes

```php
// Action Class
class Action {
    public function __construct(
        public string $index,
        public string $icon,
        public string $title,
        public string $method,
        public mixed $url,
        public ?string $frontendView = null
    ) {}
}

// MassAction Class
class MassAction {
    public function __construct(
        public string $icon,
        public string $title,
        public string $method,
        public mixed $url,
        public array $options = [],
    ) {}
}
```

### DataGrid Service Provider

**Class:** `Webkul\DataGrid\Providers\DataGridServiceProvider`

```php
public function boot(): void {}
public function register(): void {}
```

### Example: Product DataGrid Implementation

**Class:** `Webkul\Admin\DataGrids\Catalog\ProductDataGrid extends DataGrid implements ExportableInterface`

```php
use AttributeColumnTrait;

protected $primaryColumn = 'product_id';
protected $sortColumn = 'products.updated_at';
protected $elasticSearchSortColumn = 'updated_at';
protected $attributeColumns = [];
protected $productQueryBuilder;
protected bool $manageableColumn = true;

protected $defaultColumns = [
    'sku',
    'image',
    'name',
    'attribute_family',
    'status',
    'type',
    'completeness',
];

public function __construct(
    protected AttributeFamilyRepository $attributeFamilyRepository,
    protected ProductRepository $productRepository,
    protected ChannelRepository $channelRepository,
    protected ProductAttributeValuesNormalizer $valuesNormalizer,
    protected AttributeService $attributeService,
    protected AttributeValueNormalizer $attributeValueNormalizer,
) {}
```

---

## 4. Event System Infrastructure

### Event Service Provider Pattern

**Class:** `Webkul\Core\Providers\EventServiceProvider extends Illuminate\Foundation\Support\Providers\EventServiceProvider`

```php
protected $listen = [
    'Prettus\Repository\Events\RepositoryEntityCreated' => [
        'Webkul\Core\Listeners\CleanCacheRepository',
    ],
    'Prettus\Repository\Events\RepositoryEntityUpdated' => [
        'Webkul\Core\Listeners\CleanCacheRepository',
    ],
    'Prettus\Repository\Events\RepositoryEntityDeleted' => [
        'Webkul\Core\Listeners\CleanCacheRepository',
    ],
    'Spatie\ResponseCache\Events\ResponseCacheHit' => [
        'Webkul\Core\Listeners\ResponseCacheHit',
    ],
];
```

### Admin Event Service Provider

**Class:** `Webkul\Admin\Providers\EventServiceProvider`

```php
protected $listen = [
    'admin.password.update.after' => [
        'Webkul\Admin\Listeners\Admin@afterPasswordUpdated',
    ],
];
```

### FPC (Full Page Cache) Event Service Provider

**Class:** `Webkul\FPC\Providers\EventServiceProvider`

Listens to:

```php
protected $listen = [
    'catalog.product.update.after'  => ['Webkul\FPC\Listeners\Product@afterUpdate'],
    'catalog.product.delete.before' => ['Webkul\FPC\Listeners\Product@beforeDelete'],
    'catalog.category.update.after' => ['Webkul\FPC\Listeners\Category@afterUpdate'],
    'catalog.category.delete.before' => ['Webkul\FPC\Listeners\Category@beforeDelete'],
    'customer.review.update.after' => ['Webkul\FPC\Listeners\Review@afterUpdate'],
    'customer.review.delete.before' => ['Webkul\FPC\Listeners\Review@beforeDelete'],
    'checkout.order.save.after'     => ['Webkul\FPC\Listeners\Order@afterCancelOrCreate'],
    'sales.order.cancel.after'      => ['Webkul\FPC\Listeners\Order@afterCancelOrCreate'],
    'sales.refund.save.after'       => ['Webkul\FPC\Listeners\Refund@afterCreate'],
    'cms.page.update.after' => ['Webkul\FPC\Listeners\Page@afterUpdate'],
    'cms.page.delete.before' => ['Webkul\FPC\Listeners\Page@beforeDelete'],
    'theme_customization.create.after' => ['Webkul\FPC\Listeners\ThemeCustomization@afterCreate'],
    'theme_customization.update.after' => ['Webkul\FPC\Listeners\ThemeCustomization@afterUpdate'],
    'theme_customization.delete.before' => ['Webkul\FPC\Listeners\ThemeCustomization@beforeDelete'],
    'core.channel.update.after' => ['Webkul\FPC\Listeners\Channel@afterUpdate'],
    'marketing.search_seo.url_rewrites.update.after' => ['Webkul\FPC\Listeners\URLRewrite@afterUpdate'],
    'marketing.search_seo.url_rewrites.delete.before' => ['Webkul\FPC\Listeners\URLRewrite@beforeDelete'],
];
```

### Notification Event Service Provider

**Class:** `Webkul\Notification\Providers\EventServiceProvider`

```php
protected $listen = [
    \Webkul\Notification\Events\NotificationEvent::class => [
        \Webkul\Notification\Listeners\NotificationListener::class,
    ],
    'data_transfer.export.completed' => [
        'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
    ],
    'data_transfer.imports.completed' => [
        'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
    ],
    'data_transfer.import.validate.state_failed' => [
        'Webkul\Notification\Listeners\SendNotificationListener@sendNotification',
    ],
];
```

### Webhook Event Service Provider

**Class:** `Webkul\Webhook\Providers\EventServiceProvider`

```php
public function boot() {
    Event::listen('catalog.product.update.after', 'Webkul\Webhook\Listeners\Product@afterUpdate');
    Event::listen('catalog.product.create.after', 'Webkul\Webhook\Listeners\Product@afterCreate');
    Event::listen('data_transfer.imports.batch.product.save.after', 'Webkul\Webhook\Listeners\Product@afterBulkUpdate');
    Event::listen('data_transfer.imports.batch.import.before', 'Webkul\Webhook\Listeners\ImportBatch');
}
```

---

## 5. History/Versioning (Auditing) Infrastructure

### History Trait Pattern

**Class:** `Webkul\HistoryControl\Traits\HistoryTrait`

```php
use Auditable;  // From OwenIt\Auditing\Auditable

public function transformAudit(array $data): array {
    // Transform audit data for root_category_id
    // Transform translatable fields using $this->historyTranslatableFields
    // Returns transformed $data with restructured old_values and new_values
}

public function generateTags(): array {
    return $this->historyTags;
}

public function getPrimaryModelIdForHistory(): int {
    return $this->id;
}
```

### History Contract

**Interface:** `Webkul\HistoryControl\Contracts\HistoryAuditable extends OwenIt\Auditing\Contracts\Auditable`

```php
interface HistoryAuditable extends AuditableContract {
    /**
     * Get the identifier used for creating history versions.
     * Returns the ID of the main model associated with the current model.
     */
    public function getPrimaryModelIdForHistory(): int;
}
```

### History Model

**Class:** `Webkul\HistoryControl\Models\History extends Model implements HistoryContract`

```php
protected $fillable = [];
```

### History Service Providers

**Class:** `Webkul\HistoryControl\Providers\HistoryControlServiceProvider`

```php
public function boot(): void {
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->app->register(EventServiceProvider::class);
}

public function register(): void {}
```

### History Module Service Provider

**Class:** `Webkul\HistoryControl\Providers\ModuleServiceProvider extends CoreModuleServiceProvider`

```php
protected $models = [
    \Webkul\HistoryControl\Models\History::class,
];
```

### History Event Listener

**Class:** `Webkul\HistoryControl\Listeners\ProxyValueSyncEventListener`

```php
const EVENT_KEY = 'core.model.proxy.sync.';
const AUDIT_EVENT = 'updated';

public function handle($event, $data): void {
    $oldValues = $data['old_values'];
    $newValues = $data['new_values'];
    $model = $data['model'];

    if ($model instanceof HistoryContract) {
        $eventName = ucfirst(substr($event, strlen(self::EVENT_KEY)));

        if ($oldValues !== $newValues) {
            $model->auditCustomOld['common'][$eventName] = $oldValues;
            $model->auditCustomNew['common'][$eventName] = $newValues;
        }

        if (!empty($model->auditCustomOld) || !empty($model->auditCustomNew)) {
            $model->auditEvent = self::AUDIT_EVENT;
            $model->isCustomEvent = true;
            Event::dispatch(AuditCustom::class, [$model]);
        }
    }
}
```

---

## 6. Full Page Cache (FPC) Infrastructure

### FPC Service Provider

**Class:** `Webkul\FPC\Providers\FPCServiceProvider extends ServiceProvider`

```php
public function boot() {
    // EventServiceProvider registration is commented out
    // $this->app->register(EventServiceProvider::class);
}

public function register() {}
```

### FPC Event Listeners

Implements cache invalidation on entity changes:

| Listener Class | Trigger Events |
|---|---|
| `Webkul\FPC\Listeners\Product` | Product updates/deletes |
| `Webkul\FPC\Listeners\Category` | Category updates/deletes |
| `Webkul\FPC\Listeners\Review` | Review updates/deletes |
| `Webkul\FPC\Listeners\Order` | Order creation and cancellation |
| `Webkul\FPC\Listeners\Refund` | Refund creation |
| `Webkul\FPC\Listeners\Page` | CMS page updates/deletes |
| `Webkul\FPC\Listeners\ThemeCustomization` | Theme customization changes |
| `Webkul\FPC\Listeners\Channel` | Channel updates |
| `Webkul\FPC\Listeners\URLRewrite` | URL rewrite updates/deletes |

### FPC Hasher

**Class:** `Webkul\FPC\Hasher\DefaultHasher`

Provides hashing functionality for cache key generation.

---

## 7. Notification Infrastructure

### Notification Model

**Class:** `Webkul\Notification\Models\Notification extends Model implements NotificationContract`

```php
protected $fillable = [
    'type',
    'route',
    'route_params',
    'title',
    'description',
    'context',
];

protected $casts = [
    'context'      => 'array',
    'route_params' => 'array',
];

protected $appends = ['created_at_human'];

public function getCreatedAtHumanAttribute() {
    return $this->created_at->diffForHumans();
}

public function userNotifications() {
    return $this->hasMany(UserNotification::class);
}
```

### User Notification Model

**Class:** `Webkul\Notification\Models\UserNotification extends Model implements UserNotificationContract`

Linked to both `Notification` and `User` models.

### Notification Service Provider

**Class:** `Webkul\Notification\Providers\NotificationServiceProvider extends ServiceProvider`

```php
public function boot() {
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->app->register(EventServiceProvider::class);
}
```

### Notification Contract

**Interface:** `Webkul\Notification\Contracts\Notification {}`

Empty marker interface.

---

## 8. Webhook Infrastructure

### Webhook Service Provider

**Class:** `Webkul\Webhook\Providers\WebhookServiceProvider extends ServiceProvider`

```php
public function boot() {
    Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'webhook');
    $this->loadViewsFrom(__DIR__.'/../Resources/views', 'webhook');
    $this->app->register(EventServiceProvider::class);
}

public function register() {
    $this->registerConfig();
}

protected function registerConfig() {
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
}
```

### Webhook Models

**Class:** `Webkul\Webhook\Models\WebhookLog extends Model`

```php
protected $table = 'webhook_logs';
protected $fillable = ['sku', 'user', 'status', 'extra'];
protected $casts = ['extra' => 'array'];
```

**Class:** `Webkul\Webhook\Models\WebhookSetting extends Model`

Stores webhook configuration and settings.

### Webhook Event Listener

**Class:** `Webkul\Webhook\Listeners\Product`

Listens to:
- `catalog.product.update.after` -- `afterUpdate()`
- `catalog.product.create.after` -- `afterCreate()`
- `data_transfer.imports.batch.product.save.after` -- `afterBulkUpdate()`

### Webhook Repositories

- `Webkul\Webhook\Repositories\LogsRepository`
- `Webkul\Webhook\Repositories\SettingsRepository`

### Webhook Services

**Class:** `Webkul\Webhook\Services\WebhookService`

Handles webhook dispatching and management.

---

## 9. Completeness Infrastructure

### Completeness Service Provider

**Class:** `Webkul\Completeness\Providers\CompletenessServiceProvider extends ServiceProvider`

```php
public function boot() {
    $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    $this->loadViewsFrom(__DIR__.'/../Resources/views', 'completeness');
    $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'completeness');
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

    $this->app->register(ModuleServiceProvider::class);

    ProductProxy::observe(CompletenessProductObserver::class);

    Event::listen('catalog.attribute_family.attributes.changed', HandleFamilyAttributeChanges::class);

    if ($this->app->runningInConsole()) {
        $this->commands([RecalculateCompletenessCommand::class]);
    }
}
```

### Completeness Models

**Class:** `Webkul\Completeness\Models\ProductCompletenessScore extends Model`

```php
protected $table = 'product_completeness';

protected $fillable = [
    'product_id',
    'channel_id',
    'locale_id',
    'score',
    'missing_count',
];

public function product(): BelongsTo {
    return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
}

public function channel(): BelongsTo {
    return $this->belongsTo(Channel::class, 'channel_id');
}

public function locale(): BelongsTo {
    return $this->belongsTo(Locale::class, 'locale_id');
}
```

**Class:** `Webkul\Completeness\Models\CompletenessSetting extends Model`

Stores attribute/channel/locale completeness settings.

### Completeness Observer

**Class:** `Webkul\Completeness\Observers\Product`

Observes `ProductProxy` for:
- Creating completeness scores for new products
- Updating scores on product changes
- Handling cascade deletions

### Completeness Jobs

- `Webkul\Completeness\Jobs\ProductCompletenessJob` -- Single product scoring
- `Webkul\Completeness\Jobs\BulkProductCompletenessJob` -- Batch product scoring

### Completeness Event Listener

**Class:** `Webkul\Completeness\Listeners\HandleFamilyAttributeChanges`

Listens to `catalog.attribute_family.attributes.changed` event.

### Completeness Repositories

- `Webkul\Completeness\Repositories\CompletenessSettingsRepository`
- `Webkul\Completeness\Repositories\ProductCompletenessScoreRepository`

---

## 10. Product & Attribute Service Providers

### Product Service Provider

**Class:** `Webkul\Product\Providers\ProductServiceProvider extends ServiceProvider`

```php
public function boot(): void {
    include __DIR__.'/../Http/helpers.php';
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'product');
    $this->app->register(EventServiceProvider::class);
    ProductProxy::observe(ProductObserver::class);
}

public function register(): void {
    $this->registerConfig();
    $this->registerFacades();
    $this->registerTags();
}

protected function registerConfig(): void {
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/product_types.php', 'product_types');
}

protected function registerFacades(): void {
    // Registers: product_image, product_video, value_setter, product_value_mapper
}

protected function registerTags(): void {
    // Tags for ElasticSearch attribute filters
    $this->app->tag([...], 'unopim.elasticsearch.attribute.filters');
    // Tags for ElasticSearch product property filters
    $this->app->tag([...], 'unopim.elasticsearch.product.property.filters');
    // Tags for database attribute filters
    $this->app->tag([...], 'unopim.database.attribute.filters');
    // Tags for database product property filters
    $this->app->tag([...], 'unopim.database.product.property.filters');
}
```

### Attribute Service Provider

**Class:** `Webkul\Attribute\Providers\AttributeServiceProvider extends ServiceProvider`

```php
public function boot(): void {
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->registerConfig();

    $this->app->singleton(AttributeService::class, function ($app) {
        return new AttributeService(
            $app->make(AttributeRepository::class)
        );
    });
}

public function registerConfig(): void {
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_types.php', 'attribute_types');
}
```

---

## 11. Admin Service Provider Infrastructure

### Admin Service Provider

**Class:** `Webkul\Admin\Providers\AdminServiceProvider extends ServiceProvider`

```php
public function boot(Router $router) {
    Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
    $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'admin');
    $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin');
    Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'admin');
    $this->composeView();
    $this->registerACL();
    $this->app->register(EventServiceProvider::class);
}

public function register() {
    $this->registerConfig();
}

protected function registerConfig() {
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
    $this->mergeConfigFrom(dirname(__DIR__).'/Config/system.php', 'core');
}

protected function composeView() {
    // Composes menu layouts with Tree structure
    // Uses bouncer()->hasPermission() for authorization
    // Composes role management views with ACL tree
}

protected function registerACL() {
    $this->app->singleton('acl', fn() => $this->createACL());
}

protected function createACL() {
    $tree = Tree::create();
    foreach (config('acl') as $item) {
        $tree->add($item, 'acl');
    }
    $tree->items = core()->sortItems($tree->items);
    return $tree;
}
```

---

## 12. Theme Infrastructure

### Theme Service Provider

**Class:** `Webkul\Theme\Providers\ThemeServiceProvider extends ServiceProvider`

```php
public function boot() {
    include __DIR__.'/../Http/helpers.php';

    Blade::directive('unoPimVite', function ($expression) {
        return "<?php echo themes()->setUnoPimVite({$expression})->toHtml(); ?>";
    });
}

public function register() {
    $this->app->singleton('themes', fn() => new Themes);

    $this->app->singleton('view.finder', function ($app) {
        return new \Webkul\Theme\ThemeViewFinder(
            $app['files'],
            $app['config']['view.paths'],
            null
        );
    });
}
```

### View Render Event Manager

**Class:** `Webkul\Theme\ViewRenderEventManager`

```php
protected $templates = [];
protected $params;

public function handleRenderEvent($eventName, $params = null): string {
    $this->params = $params ?? [];
    Event::dispatch($eventName, $this);
    return $this->templates;
}

public function getParams(): array {}
public function getParam($name): mixed {}

public function addTemplate($template): void {
    array_push($this->templates, $template);
}

public function render(): string {
    $string = '';
    foreach ($this->templates as $template) {
        if (view()->exists($template)) {
            $string .= view($template, $this->params)->render();
        } elseif (is_string($template)) {
            $string .= $template;
        }
    }
    return $string;
}
```

### Themes Manager

**Class:** `Webkul\Theme\Themes`

Manages theme registration, loading, and customization.

### Theme View Finder

**Class:** `Webkul\Theme\ThemeViewFinder`

Extends Laravel's view finder to support theme-specific view resolution.

---

## 13. Database Grammar System

### Grammar Query Manager

**Class:** `Webkul\Core\Helpers\Database\GrammarQueryManager`

```php
protected static array $instances = [];

public static function getGrammar(?string $driver = null): Grammar {
    $driver = $driver ?? DB::getDriverName();

    if (isset(static::$instances[$driver])) {
        return static::$instances[$driver];
    }

    static::$instances[$driver] = match ($driver) {
        'pgsql' => new PostgresGrammar,
        'mysql' => new MySQLGrammar,
        'sqlite' => new SQLiteGrammar,
        default => throw new \RuntimeException("Unsupported DB driver: {$driver}")
    };

    return static::$instances[$driver];
}
```

Registers as macro: `DB::macro('rawQueryGrammar', fn() => GrammarQueryManager::getGrammar());`

### Grammar Implementations

| Class | Description |
|---|---|
| `Webkul\Core\Helpers\Database\Grammars\MySQLGrammar implements Grammar` | MySQL-specific query building and boolean handling |
| `Webkul\Core\Helpers\Database\Grammars\PostgresGrammar implements Grammar` | PostgreSQL-specific query building and boolean handling |
| `Webkul\Core\Helpers\Database\Grammars\SQLiteGrammar implements Grammar` | SQLite-specific query building and boolean handling |

Each implements DB-specific query building and boolean value handling.

---

## 14. Repository Pattern Configuration

**Config File:** `/config/repository.php`

**Key Features:**
- Pagination limit: 15 (default)
- Fractal presenter with DataArraySerializer
- Cache configuration with repository-specific settings
- Cache clean listeners on created/updated/deleted events
- Criteria configuration with accepted conditions: `=`, `like`, `in`
- Request parameter mapping: search, searchFields, filter, orderBy, sortedBy, with, searchJoin, withCount

**Cache-enabled repositories:**

| Repository | Description |
|---|---|
| `Webkul\Core\Repositories\CoreConfigRepository` | Core configuration cache |
| `Webkul\Core\Repositories\ChannelRepository` | Channel data cache |
| `Webkul\Core\Repositories\CurrencyRepository` | Currency data cache |
| `Webkul\Core\Repositories\LocaleRepository` | Locale data cache |

---

## 15. Build & Asset Pipeline

### Root Vite Configuration

**File:** `/vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### Theme-specific Vite Configs

- `/packages/Webkul/Admin/vite.config.js`
- `/packages/Webkul/Installer/vite.config.js`

Each theme/package can define custom build entry points and asset processing.

---

## 16. Core Configuration Patterns

### Publishable Config Files from CoreServiceProvider

| Config File | Purpose |
|---|---|
| `config/concord.php` | Module registration |
| `config/repository.php` | Repository pattern settings |
| `config/visitor.php` | Visitor tracking configuration |
| `config/elasticsearch.php` | ElasticSearch connection settings |

### Key Facades & Singletons

| Alias | Resolved Class | Description |
|---|---|---|
| `core` | `Core` class | Core system facade |
| `elasticsearch` | `ElasticSearch` class | ElasticSearch client connection |
| `acl` | ACL Tree (Admin) | Access control list tree |
| `themes` | `Themes` manager | Theme management |
| `product_image` | `ProductImage` helper | Product image processing |
| `product_video` | `ProductVideo` helper | Product video processing |
| `value_setter` | `ValueSetter` helper | Product value setter |
| `product_value_mapper` | `ProductValueMapper` helper | Product value mapping |

---

## 17. Key Inheritance Chains

### Service Provider Hierarchy

```
Illuminate\Support\ServiceProvider
  +-- Webkul\Core\Providers\CoreServiceProvider
  +-- Webkul\Admin\Providers\AdminServiceProvider
  +-- Webkul\Product\Providers\ProductServiceProvider
  +-- Webkul\Attribute\Providers\AttributeServiceProvider
  +-- Webkul\HistoryControl\Providers\HistoryControlServiceProvider
  +-- Webkul\Notification\Providers\NotificationServiceProvider
  +-- Webkul\Webhook\Providers\WebhookServiceProvider
  +-- Webkul\Theme\Providers\ThemeServiceProvider
  +-- Webkul\Completeness\Providers\CompletenessServiceProvider

Illuminate\Foundation\Support\Providers\EventServiceProvider
  +-- Webkul\Core\Providers\EventServiceProvider
  +-- Webkul\Admin\Providers\EventServiceProvider
  +-- Webkul\FPC\Providers\EventServiceProvider
  +-- Webkul\Notification\Providers\EventServiceProvider
  +-- Webkul\Webhook\Providers\EventServiceProvider
  +-- Webkul\Product\Providers\EventServiceProvider
```

### Module Service Provider Hierarchy

```
Konekt\Concord\BaseModuleServiceProvider
  +-- Webkul\Core\Providers\CoreModuleServiceProvider
        +-- Webkul\Core\Providers\ModuleServiceProvider
        +-- Webkul\Admin\Providers\ModuleServiceProvider
        +-- Webkul\Product\Providers\ModuleServiceProvider
        +-- Webkul\Attribute\Providers\ModuleServiceProvider
        +-- Webkul\HistoryControl\Providers\ModuleServiceProvider
        +-- Webkul\Notification\Providers\ModuleServiceProvider
        +-- Webkul\Webhook\Providers\ModuleServiceProvider
        +-- Webkul\Theme\Providers\ModuleServiceProvider
        +-- Webkul\Completeness\Providers\ModuleServiceProvider
```

### Model Hierarchy

```
Illuminate\Database\Eloquent\Model
  +-- Webkul\HistoryControl\Models\History (implements HistoryContract)
  +-- Webkul\Notification\Models\Notification (implements NotificationContract)
  +-- Webkul\Notification\Models\UserNotification (implements UserNotificationContract)
  +-- Webkul\Webhook\Models\WebhookLog
  +-- Webkul\Webhook\Models\WebhookSetting
  +-- Webkul\Completeness\Models\ProductCompletenessScore
```

### Model Traits

```
OwenIt\Auditing\Auditable (extended by HistoryTrait)
  +-- Webkul\HistoryControl\Traits\HistoryTrait
```

### DataGrid Hierarchy

```
Webkul\DataGrid\DataGrid (abstract)
  +-- Webkul\Admin\DataGrids\Catalog\ProductDataGrid (implements ExportableInterface)
  +-- Various other domain-specific DataGrids
```

---

## 18. Configuration Merging Patterns

Service providers use `mergeConfigFrom()` to safely merge package configs:

```php
// Admin Service Provider
$this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
$this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
$this->mergeConfigFrom(dirname(__DIR__).'/Config/system.php', 'core');

// Webhook Service Provider
$this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
$this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

// Product Service Provider
$this->mergeConfigFrom(dirname(__DIR__).'/Config/product_types.php', 'product_types');

// Attribute Service Provider
$this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_types.php', 'attribute_types');
```

This pattern allows multiple packages to contribute to the same config key (e.g., `menu.admin` and `acl`), enabling modular menu items and ACL rules that are automatically merged at boot time.

---

## 19. Request Validation & Filtering Patterns

### DataGrid Request Validation

```php
// Validated fields
'filters'     => ['sometimes', 'required', 'array'],
'sort'        => ['sometimes', 'required', 'array'],
'pagination'  => ['sometimes', 'required', 'array'],
'export'      => ['sometimes', 'required', 'boolean'],
'format'      => ['sometimes', 'required', 'in:csv,xls,xlsx'],
'productIds'  => ['sometimes', 'array'],
```

### Filter Type Mapping

- **All** -- Full-text search across searchable columns
- **Column-specific** -- Type-aware filtering based on `ColumnTypeEnum`

---

## 20. Lazy Loading & Deferred Registration

### Deferred Services Pattern

Most domain services use `$app->singleton()` for lazy registration to improve bootstrap performance.

**Examples:**

| Singleton | Purpose |
|---|---|
| Core singleton registration | Core facade lazy-loaded on first access |
| ElasticSearch client registration | ES connection created only when needed |
| Theme manager registration | Theme system initialized on demand |
| Product image/video helpers | Media helpers instantiated on first use |
| Attribute service | Attribute logic loaded when queried |

This defers actual instantiation until first use, improving application startup time by avoiding eager loading of services that may not be needed for every request.

---

*End of Infrastructure Layer documentation.*
