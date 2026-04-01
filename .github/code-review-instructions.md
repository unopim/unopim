# Unopim Connector — Code Review Instructions

Use this checklist when reviewing Laravel 11 + Unopim connector/plugin code.
Applies to all AI coding agents: Kilo Code, GitHub Copilot, Claude Code, Codex, Cursor.



## 1. Documentation Review

- [ ] PHPDoc blocks (`/** */`) used on all non-trivial methods
- [ ] No verbose inline comments that repeat the code
- [ ] `@return` and `@param` present for complex types
- [ ] Dead code removed



## 2. Code Structure Review

- [ ] Constructor injection for all dependencies (no `app()` calls inside methods unless deferred)
- [ ] Early returns / guard clauses reduce nesting
- [ ] All parameters and return types have type hints
- [ ] PSR-12 compliance (spacing, bracket placement, etc.)
- [ ] No business logic in controllers



## 3. Unopim Architecture Review (CRITICAL)

### 3.1 Table Names
- [ ] All tables prefixed with `wk_` — e.g., `wk_woocommerce_credentials`
- [ ] Pattern: `wk_{module}_{entity_plural}`
- [ ] Migration in `Database/Migration/` at package root (not `Migrations`)

### 3.2 Models
- [ ] Implements `PresentableHistoryInterface`
- [ ] Uses `HistoryTrait` from `Webkul\HistoryControl`
- [ ] Sensitive fields listed in `$auditExclude`
- [ ] Has matching Contract interface in `src/Contracts/`
- [ ] Uses `extras` JSON column for flexible config (not scattered separate columns)

### 3.3 ModuleServiceProvider
- [ ] Extends `Webkul\Core\Providers\CoreModuleServiceProvider`
- [ ] Only declares `protected $models = [...]` — no manual binding
- [ ] Does NOT duplicate functionality from main ServiceProvider

### 3.4 Main ServiceProvider
- [ ] Routes loaded via `Route::middleware('web')->group(...)`
- [ ] Migration path is `Database/Migration` (not `Database/Migrations`)
- [ ] Event listener uses `unopim.admin.layout.head.before` (with `.before` — NOT without)
- [ ] All 5 config files merged: `acl.php`, `menu.php`, `exporters.php`, `quick_exporters.php`, `importers.php`
- [ ] ModuleServiceProvider registered in `register()` (NOT in `boot()`)

### 3.5 Routes
- [ ] Admin group uses `middleware => ['admin']` only
- [ ] Prefix is `config('app.admin_url')` — NOT hardcoded `'admin'`
- [ ] Route names follow `{module}.{section}.{action}` convention
- [ ] Webhook route has `->withoutMiddleware(VerifyCsrfToken::class)`
- [ ] No `Route::resource()` usage

### 3.6 Controllers
- [ ] Uses `Http/Requests/` Form Request for validation (NOT inline `$request->validate()`)
- [ ] `index()` returns `app(XyzDataGrid::class)->toJson()` for AJAX, view otherwise
- [ ] `store()`/`update()` return `JsonResponse` with `redirect_url` key
- [ ] No direct DB calls in controller — delegates to repository/service

### 3.7 ACL
- [ ] Flat array — no nested `children` keys
- [ ] Each entry has: `key`, `name`, `route`, `sort`
- [ ] Name uses language key `{module}::app.acl.*`



## 4. DataGrid Review

- [ ] Located in `src/DataGrids/{Section}/{Entity}DataGrid.php` subdirectory
- [ ] Extends `Webkul\DataGrid\DataGrid`
- [ ] `prepareQueryBuilder()` uses `DB::table()` (not Eloquent)
- [ ] `url` callback in actions uses `function ($row)` (not arrow function)
- [ ] `bouncer()->hasPermission()` gates all actions and mass actions
- [ ] Column closures use Unopim span classes: `label-active`, `label-info`
- [ ] PHPDoc `@return` added to `prepareQueryBuilder()`



## 5. Exporters Review

- [ ] `exporters.php` present for batch/scheduled exports
- [ ] `quick_exporters.php` present for one-click export
- [ ] `importers.php` present if connector supports import
- [ ] Filter fields have correct keys: `name`, `title`, `required`, `validation`, `type`, `async`, `track_by`, `label_by`, `list_route`
- [ ] `list_route` routes exist and return `[{id, label}]` format
- [ ] Exporter class extends Unopim base exporter



## 6. HTTP Client Review

- [ ] Uses cURL (NOT Guzzle) matching Unopim connector pattern
- [ ] `testConnection()` method present and called before saving credentials
- [ ] Credentials NOT passed to controller — Service class mediates
- [ ] No raw credentials logged — `Log::error()` with sanitized context only
- [ ] SSL verification enabled by default (`CURLOPT_SSL_VERIFYPEER => true`)
- [ ] `curl_close()` called in all code paths



## 7. Credentials Management Review

- [ ] `extras` JSON column used for flexible/additional config
- [ ] Sensitive fields (`consumerSecret`, `password`, etc.) in `$auditExclude`
- [ ] `testConnection()` called in `store()` BEFORE persisting
- [ ] Edit form blanks password/secret fields
- [ ] Update flow: if secret empty → keep old value; if provided → update
- [ ] Unique constraint on identifying field (e.g., `shopUrl`)



## 8. Performance Review

- [ ] No N+1 queries — eager loading used where relationships traversed
- [ ] Large data sets chunked (export batch size configurable)
- [ ] `DB::table()` in DataGrids (not Eloquent)
- [ ] Foreign key columns indexed in migrations
- [ ] No `SELECT *` in DataGrid query builder



## 9. Security Review

- [ ] All admin routes protected by `['admin']` middleware
- [ ] `bouncer()->hasPermission()` on every DataGrid action
- [ ] Webhook routes use `withoutMiddleware(VerifyCsrfToken::class)` explicitly
- [ ] No raw credentials or secrets in logs
- [ ] Input validated via Form Request before use
- [ ] Unique constraints on credential identifiers



## 10. Translation Keys Review

- [ ] All user-facing strings use translation keys `{module}::app.*`
- [ ] Keys exist in `Resources/lang/en/app.php`
- [ ] DataGrid labels use `trans()` calls
- [ ] Error messages use translation keys (not hardcoded English)



## 11. Review Priority Levels

| Priority   | Examples                                                                      |
| - | -- |
| 🔴 Critical | Credentials saved without testing, missing `wk_` prefix, wrong middleware     |
| 🟠 High     | Missing `auditExclude`, wrong event name (missing `.before`), N+1 queries     |
| 🟡 Medium   | Inline validation (should be Form Request), missing type hints, no DataGrid   |
| 🟢 Low      | PHPDoc style, minor label translations, sort order in ACL/menu               |



## 12. Quick Compatibility Checklist

Before marking code ready for any agent or project:

- [ ] `wk_` prefix on all tables
- [ ] `HistoryTrait` + `PresentableHistoryInterface` on all models
- [ ] `CoreModuleServiceProvider` for ModuleServiceProvider
- [ ] Correct event name: `unopim.admin.layout.head.before`
- [ ] ACL is flat (no nested children)
- [ ] DataGrid in subdirectory, uses `DB::table()`
- [ ] Three exporter configs: `exporters.php`, `quick_exporters.php`, `importers.php`
- [ ] Form Requests for all validation
- [ ] `JsonResponse` with `redirect_url` from store/update
- [ ] cURL client (not Guzzle)
- [ ] `testConnection()` called before credential save
- [ ] All skills referenced in `code-generation-instructions.md` consulted
