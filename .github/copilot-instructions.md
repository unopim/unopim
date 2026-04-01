# GitHub Copilot Workspace Instructions: Unopim Connector Development

This workspace contains agent skills for building **Unopim third-party connector
modules**. Unopim is a Webkul Laravel 11 PIM — it is NOT Bagisto.

---

## Framework

- **Platform:** Unopim (not Bagisto, not Magento, not Akeneo)
- **Language:** PHP 8.1+ / Laravel 11
- **Module base:** `packages/Webkul/{ModuleName}/src/`
- **Reference:** `packages/Webkul/WooCommerce/` is the ground-truth connector

---

## Non-negotiable Conventions

### 1. Table names always start with `wk_`
```php
// Correct
protected $table = 'wk_woocommerce_credentials';
DB::table('wk_shopify_products')

// Wrong
protected $table = 'woocommerce_credentials';
```

### 2. Migration folder: `Database/Migration/` (no 's')
```
Database/Migration/2025_01_01_000000_wk_module_credentials.php
```

### 3. ServiceProvider: routes via `Route::middleware('web')->group()`
```php
// Correct
Route::middleware('web')->group(__DIR__ . '/../../Routes/module-routes.php');

// Wrong
$this->loadRoutesFrom(__DIR__ . '/../../Routes/module-routes.php');
```

### 4. Layout event name includes `.before`
```php
Event::listen('unopim.admin.layout.head.before', ...);
// NOT: 'unopim.admin.layout.head'
```

### 5. ModuleServiceProvider extends CoreModuleServiceProvider
```php
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [\Webkul\WooCommerce\Models\Credential::class];
}
```

### 6. Models use HistoryTrait (not optional)
```php
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;

class Credential extends Model implements PresentableHistoryInterface
{
    use HistoryTrait;
    protected $auditExclude = ['apiSecret'];  // exclude sensitive fields
    protected $casts = ['extras' => 'array']; // flexible JSON column
}
```

### 7. HTTP client uses cURL (not Guzzle)
```php
// Correct — cURL
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, ...]);
$response = curl_exec($ch);
curl_close($ch);
```

### 8. Controller responses for mutations
```php
// store/update/delete return JsonResponse with redirect_url
return new JsonResponse([
    'redirect_url' => route('module.credentials.index'),
    'message' => 'Created successfully.',
]);
```

### 9. ACL is a flat array
```php
return [
    ['key' => 'module', 'name' => '...', 'route' => '...', 'sort' => 1],
    ['key' => 'module.credentials', ...],
    // NO nested children
];
```

### 10. Exporter filter fields need 4 required keys
```php
[
    'name'       => 'credential',
    'type'       => 'select',
    'async'      => true,
    'track_by'   => 'id',
    'label_by'   => 'label',
    'list_route' => 'module.credentials.get',
]
```

### 11. DataGrid in subdirectory with PHPDoc return types
```php
// File: src/DataGrids/Credential/CredentialDataGrid.php
class CredentialDataGrid extends DataGrid
{
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()     // no PHP return type hint
    {
        return DB::table('wk_module_credentials')->select(...);
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'   => 'status',
            'closure' => fn ($row) => $row->status    // arrow function OK
                ? '<span class="label-active">Yes</span>'
                : '<span class="label-info text-gray-600 dark:text-gray-300">No</span>',
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'url' => function ($row) {            // regular function for url
                return route('module.credentials.edit', $row->id);
            },
        ]);
    }
}
```

---

## Skills Reference

When working on a connector, load the appropriate skill file from
`.kilocode/skills-code/` for complete implementation templates:

| Task | Skill file |
|---|---|
| Create connector from scratch | `unopim-connector-quickstart/SKILL.md` |
| Module boilerplate | `unopim-package/SKILL.md` |
| Credential management | `unopim-credential-management/SKILL.md` |
| HTTP/API client | `unopim-http-client/SKILL.md` |
| Export/import jobs | `unopim-export-workflow/SKILL.md` |
| DataGrid listing | `unopim-datagrid/SKILL.md` |
| module mapping | `unopim-connector-export-mapping/SKILL.md` |

---

## Code Style

- PSR-12 formatting
- PHPDoc on all public methods
- No hardcoded UI strings — use lang files
- Sensitive API credentials belong in `$auditExclude`, never in `Crypt::encryptString`
- Permission guard: `bouncer()->hasPermission('module.resource.action')`
