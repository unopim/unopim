# Unopim Connector — Code Generation Instructions

Follow these guidelines when generating code for **Laravel 11 + Unopim** connector and plugin packages.
Applies to all AI coding agents: Kilo Code, GitHub Copilot, Claude Code, Codex, Cursor.

---

## 1. Documentation Standards

- Use Laravel 11 PHPDoc block comments (`/** */`) — not inline `//` comments
- Document method **intent** only; skip obvious implementation details
- Remove unnecessary comments and dead code
- Use `@return`, `@param` in PHPDoc when types are complex or non-obvious

---

## 2. Code Structure

- Use **constructor dependency injection** for all services and repositories
- **Return early** (guard clauses first) to reduce nesting
- Use **type hints** for all parameters and return types
- Follow **PSR-12** coding standards
- Prefer `match()` over chains of `if/elseif` for status/type mapping

---

## 3. Unopim Module Conventions

### 3.1 Package Location & Namespace

```
packages/Webkul/{ModuleName}/
Namespace: Webkul\{ModuleName}\
Composer:  webkul/{module-lowercase}
```

### 3.2 Database Table Naming (STRICT: `wk_` prefix)

- All tables MUST use the `wk_` prefix
- Pattern: `wk_{module}_{entity_plural}`
- Examples: `wk_woocommerce_credentials`, `wk_shopware_credentials`, `wk_module_attribute_mappings`
- Migration folder: `Database/Migration/` (NOT `Migrations`) — at package root, not inside `src/`

### 3.3 Model Pattern (MANDATORY)

Every Eloquent model MUST implement HistoryControl:

```php
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\{Module}\Contracts\{Entity} as {Entity}Contract;

class {Entity} extends Model implements {Entity}Contract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    protected $table  = 'wk_{module}_{entities}';
    protected $auditExclude  = ['secretField'];  // exclude secrets from audit trail

    protected $fillable = ['shopUrl', 'consumerKey', 'consumerSecret', 'active', 'defaultSet', 'extras'];
    protected $casts    = ['extras' => 'array'];  // extras JSON for flexible/additional config
}
```

### 3.4 Contract Interface (per model)

```php
// src/Contracts/{Entity}.php
namespace Webkul\{Module}\Contracts;
interface {Entity} {}
```

### 3.5 ModuleServiceProvider (CoreModuleServiceProvider)

```php
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\{Module}\Models\Credential::class,
        \Webkul\{Module}\Models\Mapping::class,
    ];
}
```

### 3.6 Main ServiceProvider — boot()

```php
public function boot(): void
{
    // Routes — MUST use Route::middleware('web')->group(), NOT $this->loadRoutesFrom()
    Route::middleware('web')->group(__DIR__ . '/../../Routes/{module}-routes.php');
    $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migration');
    $this->loadViewsFrom(__DIR__ . '/../../Resources/views', '{module}');
    $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', '{module}');

    // Real-time sync (optional)
    Event::listen('catalog.product.update.after', '{Module}Listener@syncProduct');
    Event::listen('catalog.product.create.after', '{Module}Listener@syncProduct');
    Event::listen('catalog.product.delete.before', '{Module}Listener@deleteProduct');

    if ($this->app->runningInConsole()) {
        $this->commands([{Module}Installer::class]);
    }

    // Head injection — note '.before' suffix (NOT 'unopim.admin.layout.head')
    Event::listen('unopim.admin.layout.head.before', static function (ViewRenderEventManager $viewRenderEventManager) {
        $viewRenderEventManager->addTemplate('{module}::icon-style');
    });

    $this->publishes([
        __DIR__ . '/../../publishable' => public_path('themes/{module}'),
    ], '{module}-config');
}
```

### 3.7 Main ServiceProvider — register()

```php
public function register(): void
{
    $this->app->register(ModuleServiceProvider::class);
    $this->registerConfig();
}

protected function registerConfig(): void
{
    $this->mergeConfigFrom(__DIR__ . '/../../Config/menu.php',            'menu');
    $this->mergeConfigFrom(__DIR__ . '/../../Config/acl.php',             'acl');
    $this->mergeConfigFrom(__DIR__ . '/../../Config/exporters.php',       'exporters');
    $this->mergeConfigFrom(__DIR__ . '/../../Config/importers.php',       'importers');
    $this->mergeConfigFrom(__DIR__ . '/../../Config/quick_exporters.php', 'quick_exporters');
}
```

---

## 4. Route Conventions

- Middleware: `['admin']` only (routes file already wrapped in `Route::middleware('web')` in ServiceProvider)
- Prefix: always `config('app.admin_url')`
- Route names: `{module}.{section}.{action}`
- Public webhooks go OUTSIDE admin group

```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('{module}')->group(function () {
        Route::controller(CredentialController::class)->prefix('credentials')->group(function () {
            Route::get('',               'index')      ->name('{module}.credentials.index');
            Route::post('create',        'store')      ->name('{module}.credentials.store');
            Route::get('edit/{id}',      'edit')       ->name('{module}.credentials.edit');
            Route::put('update/{id}',    'update')     ->name('{module}.credentials.update');
            Route::delete('delete/{id}', 'destroy')    ->name('{module}.credentials.delete');
            Route::post('mass-update',   'massUpdate') ->name('{module}.credentials.mass_update');
            Route::post('mass-delete',   'massDestroy')->name('{module}.credentials.mass_delete');
        });
    });
});

// Webhook — no admin middleware, no CSRF
Route::post('{module}/callback', [WebhookController::class, 'handleWebhook'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
```

---

## 5. Controller Pattern

- Thin controllers — no business logic, no DB calls
- Use dedicated `Http/Requests/` Form Request classes for validation
- Return `JsonResponse` with `redirect_url` key for store/update
- Return `app(XyzDataGrid::class)->toJson()` for AJAX calls

```php
public function index()
{
    if (request()->ajax()) {
        return app(CredentialDataGrid::class)->toJson();
    }
    return view('{module}::credentials.index');
}

public function store(CredentialForm $request): JsonResponse
{
    $response = $this->service->testConnection($request->validated());

    if ($response['code'] !== 200) {
        return new JsonResponse(['errors' => ['shopUrl' => [$response['message'] ?? 'Invalid credentials']]], 422);
    }

    $record = $this->credentialRepository->create($request->validated());

    return new JsonResponse(['redirect_url' => route('{module}.credentials.edit', $record->id)]);
}
```

---

## 6. Form Request Validation

```php
namespace Webkul\{Module}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CredentialForm extends FormRequest
{
    public function rules(): array
    {
        return [
            'shopUrl'        => 'required|url|unique:wk_{module}_credentials',
            'consumerKey'    => 'required|string',
            'consumerSecret' => 'required|string',
        ];
    }
}
```

---

## 7. ACL Configuration (Flat List)

ACL is a **flat array** — NO nested children:

```php
// Config/acl.php
return [
    ['key' => '{module}',                        'name' => '{module}::app.acl.{module}',                   'route' => '{module}.credentials.index', 'sort' => 8],
    ['key' => '{module}.credentials',            'name' => '{module}::app.acl.credentials.index',          'route' => '{module}.credentials.index', 'sort' => 1],
    ['key' => '{module}.credentials.create',     'name' => '{module}::app.acl.credentials.create',         'route' => '{module}.credentials.store', 'sort' => 1],
    ['key' => '{module}.credentials.edit',       'name' => '{module}::app.acl.credentials.edit',           'route' => '{module}.credentials.edit',  'sort' => 2],
    ['key' => '{module}.credentials.delete',     'name' => '{module}::app.acl.credentials.delete',         'route' => '{module}.credentials.delete','sort' => 2],
    ['key' => '{module}.credentials.mass-delete','name' => '{module}::app.acl.credentials.mass-delete',    'route' => '{module}.credentials.mass_delete','sort' => 3],
];
```

---

## 8. DataGrid Pattern

DataGrids live in subdirectories: `src/DataGrids/{Section}/{Entity}DataGrid.php`

```php
namespace Webkul\{Module}\DataGrids\Credential;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    /** @return \Illuminate\Database\Query\Builder */
    public function prepareQueryBuilder()
    {
        return DB::table('wk_{module}_credentials')
            ->select('id', 'shopUrl', 'consumerKey', 'active', 'defaultSet');
    }

    /** @return void */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'shopUrl', 'label' => trans('{module}::app.datagrid.shopUrl'),
            'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true,
        ]);
        $this->addColumn([
            'index'   => 'active',
            'label'   => trans('{module}::app.datagrid.active'),
            'type'    => 'boolean',
            'closure' => fn ($row) => $row->active
                ? '<span class="label-active">'.trans('admin::app.common.yes').'</span>'
                : '<span class="label-info text-gray-600 dark:text-gray-300">'.trans('admin::app.common.no').'</span>',
        ]);
    }

    /** @return void */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('{module}.credentials.edit')) {
            $this->addAction([
                'icon' => 'icon-edit', 'title' => trans('admin::app.catalog.attributes.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) { return route('{module}.credentials.edit', $row->id); },
            ]);
        }
        if (bouncer()->hasPermission('{module}.credentials.delete')) {
            $this->addAction([
                'icon' => 'icon-delete', 'title' => trans('admin::app.catalog.attributes.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) { return route('{module}.credentials.delete', $row->id); },
            ]);
        }
    }

    /** @return void */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('{module}.credentials.mass-delete')) {
            $this->addMassAction([
                'title'  => trans('admin::app.common.delete'),
                'url'    => route('{module}.credentials.mass_delete'),
                'method' => 'POST',
            ]);
        }
    }
}
```

---

## 9. Exporters Config Structure

**`Config/exporters.php`** (batch/scheduled):
```php
return [
    '{Module}Products' => [
        'title'    => '{module}::app.data-transfer.exports.type.product',
        'exporter' => 'Webkul\{Module}\Helpers\Exporters\Product\Exporter',
        'validator'=> 'Webkul\{Module}\Validators\JobInstances\Export\ProductsValidator',
        'source'   => 'Webkul\Product\Repositories\ProductRepository',
        'filters'  => [
            'fields' => [
                ['name' => 'credential', 'title' => '{module}::app.filters.credential', 'required' => true, 'validation' => 'required', 'type' => 'select', 'async' => true, 'track_by' => 'id', 'label_by' => 'label', 'list_route' => '{module}.credentials.get'],
                ['name' => 'channel',    'title' => '{module}::app.filters.channel',    'required' => true, 'validation' => 'required', 'type' => 'select', 'async' => true, 'track_by' => 'id', 'label_by' => 'label', 'list_route' => '{module}.channel.get'],
                ['name' => 'locale',     'title' => '{module}::app.filters.locale',     'required' => true, 'validation' => 'required', 'type' => 'select', 'async' => true, 'track_by' => 'id', 'label_by' => 'label', 'list_route' => '{module}.locale.get'],
            ],
        ],
    ],
];
```

**`Config/quick_exporters.php`** (one-click from product grid):
```php
return [
    '{Module}QuickExport' => [
        'title'    => '{Module} Quick Export Product',
        'route'    => '{module}.quick_export',
        'exporter' => 'Webkul\{Module}\Helpers\Exporters\Product\Exporter',
        'source'   => 'Webkul\Product\Repositories\ProductRepository',
    ],
];
```

---

## 10. HTTP Client Pattern

Use cURL-based clients (matching Unopim's WooCommerce/real-world pattern):

```php
class {Module}ApiClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
        protected string $apiSecret,
        protected array  $options = []
    ) {}

    public function request(string $endpoint, array $parameters = [], array $payload = [], string $method = 'GET'): array
    {
        $ch  = curl_init();
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        $basicKey = base64_encode($this->apiKey . ':' . $this->apiSecret);
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $basicKey, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $this->options['timeout'] ?? 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("cURL error [{$errno}]: " . curl_strerror($errno));
        }

        return ['code' => $httpCode, 'data' => json_decode($response, true) ?? []];
    }

    public function testConnection(): array
    {
        return $this->request('wp-json/wc/v3/settings');  // or lightest available endpoint
    }
}
```

---

## 11. Credentials — `extras` JSON Field

Use a single `extras` JSON column for flexible additional config (do NOT create separate columns for every config key):

```php
// Migration
$table->json('extras')->nullable();

// Model
protected $casts = ['extras' => 'array'];

// Usage — store/retrieve nested config
$credential->extras['attributeMapping'] = [...];
$credential->extras['defaultSet'] = true;
```

---

## 12. Performance & Quality

- Use `DB::table()` in DataGrids (not Eloquent models)
- Chunk large datasets (≥ 100 records) in exporters
- Use eager loading (`->with()`) to prevent N+1 queries
- Use `$request->validated()` for all controller input
- Index FK columns in all migrations
- Log with `Log::error()` — **never** log raw secrets or decrypted credentials

---

## 13. Skills Cross-Reference

| Task                           | Skill to activate                |
| ------------------------------ | -------------------------------- |
| Full module scaffold           | `unopim-package`                 |
| HTTP client + auth             | `unopim-http-client`             |
| Credentials CRUD + encryption  | `unopim-credential-management`   |
| Attribute mapping UI           | `unopim-connector-export-mapping`   |
| Export / Import / Quick export | `unopim-export-workflow`         |
| Admin listing pages            | `unopim-datagrid`                |
| Full connector in 1 day        | `unopim-connector-quickstart`    |
