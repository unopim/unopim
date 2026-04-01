# Unopim Connector Development: Agent Instructions

This repository contains reusable agent skills for building Unopim third-party
connector modules (integrations with WooCommerce, Shopify, Shopware, module,
and any REST API). These instructions apply to all AI coding agents (Codex,
Copilot, Claude, Kilo Code, Cursor, etc.).

---

## Framework Context

- **Platform:** Unopim (Webkul) — Laravel 11 modular PIM system
- **Not Bagisto** — Unopim and Bagisto are different products. Do NOT use Bagisto patterns.
- **Package location:** `packages/Webkul/{ModuleName}/src/`
- **Reference implementation:** `packages/Webkul/WooCommerce/` (production reference)

---

## Critical Conventions (Never Deviate)

### Table Naming
- All tables: `wk_` prefix (e.g. `wk_woocommerce_credentials`, `wk_module_mappings`)
- Never: `module_credentials`, `shopify_credentials` — always add `wk_`

### Folder Paths
- Migration folder: `Database/Migration/` at package root — NOT `Migrations` (no 's')
- DataGrid location: `src/DataGrids/{Section}/{Name}DataGrid.php` — always in subdirectory

### ServiceProvider
```php
// Routes: use Route::middleware — NEVER $this->loadRoutesFrom()
Route::middleware('web')->group(__DIR__ . '/../../Routes/{module-name}-routes.php');

// Event name ends with .before — NEVER omit it
Event::listen('unopim.admin.layout.head.before', ...);
```

### Route Middleware
- Use `['admin']` only — NEVER `['web', 'admin']`

### Models (HistoryTrait required)
```php
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;

class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $table = 'wk_{module}_credentials';
    protected $casts = ['extras' => 'array'];
    protected $auditExclude = ['consumerSecret'];   // NOT Crypt::encryptString
}
```

### ModuleServiceProvider (model binding)
```php
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [\Webkul\{ModuleName}\Models\Credential::class];
}
// Register it inside main ServiceProvider::register()
```

### ACL — Flat array, no children
```php
return [
    ['key' => 'woocommerce', 'name' => '...', 'route' => '...', 'sort' => 1],
    ['key' => 'woocommerce.credentials', ...],
    // NO nested 'children' arrays
];
```

### Controllers return JsonResponse
```php
// store/update/delete return JSON with redirect_url
return new JsonResponse([
    'redirect_url' => route('{module-slug}.credentials.index'),
    'message' => '...',
]);
```

### FormRequest (never inline validate)
```php
// CORRECT: type-hint the FormRequest
public function store(CredentialForm $request): JsonResponse { ... }

// WRONG: inline validation
public function store(Request $request) {
    $request->validate([...]); // Never do this
}
```

### HTTP Client (cURL, not Guzzle)
```php
// CORRECT
$ch = curl_init();
curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true]);
$response = curl_exec($ch);
curl_close($ch);

// WRONG — Guzzle is NOT used in Unopim connectors
$client = new \GuzzleHttp\Client();
$response = $client->get($url);
```

### DataGrid Callbacks
```php
// Column closure: arrow function OK
'closure' => fn ($row) => $row->status ? '<span class="label-active">Yes</span>' : '<span class="label-info text-gray-600 dark:text-gray-300">No</span>',

// Action URL: regular function required (must return string)
'url' => function ($row) {
    return route('{module-slug}.credentials.edit', $row->id);
},
```

### DataGrid Methods: PHPDoc return types only
```php
// CORRECT
/**
 * @return \Illuminate\Database\Query\Builder
 */
public function prepareQueryBuilder() { ... }

// WRONG — no PHP return type hints on DataGrid methods
public function prepareQueryBuilder(): Builder { ... }
```

### Exporter Filter Fields (4 required keys)
```php
// Every select-type filter field MUST have all 4 of these
[
    'name'       => 'credential',
    'type'       => 'select',
    'async'      => true,                       // required
    'track_by'   => 'id',                       // required
    'label_by'   => 'label',                    // required
    'list_route' => '{module-slug}.credentials.get',  // required route name
]
```

### Three Config Files
All connectors must have all three:
- `exporters.php` — scheduled/manual export jobs
- `quick_exporters.php` — one-click export from product listing
- `importers.php` — import jobs

---

## Skills Available

Load these at the start of your task for detailed implementation guidance:

| Goal | Skill name |
|---|---|
| Start a connector from scratch | `unopim-connector-quickstart` |
| Full module boilerplate | `unopim-package` |
| Credential CRUD + model | `unopim-credential-management` |
| HTTP client (cURL) | `unopim-http-client` |
| Export/import workflow | `unopim-export-workflow` |
| DataGrid listing | `unopim-datagrid` |
| module mapping | `unopim-connector-export-mapping` |

Skills are located in `.kilocode/skills-code/` (Kilo Code),
`.claude/skills/` (Claude), and `.github/skills/` (other agents).

---

## Code Quality Standards

- PSR-12 code style
- All public methods have PHPDoc blocks
- Translations use lang files — never hardcode UI strings
- `extras` JSON column for flexible config (never add many one-off columns)
- All sensitive fields in `$auditExclude` — never in `Crypt::encryptString`
- Service class mediates all API calls — controllers are thin

---

## Do NOT

- Use Bagisto patterns (different platform)
- Use Guzzle — use native cURL
- Use `Crypt::encryptString()` for API secrets — use `$auditExclude`
- Use `$this->loadRoutesFrom()` — use `Route::middleware('web')->group()`
- Omit `.before` from the layout event name
- Use `['web', 'admin']` middleware — use `['admin']` only
- Create DataGrid in root `DataGrids/` — always use a subdirectory
- Use nested `children` in ACL config
- Use inline `$request->validate()` in controllers
