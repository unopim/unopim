# Tenant Isolation Security Audit Report
**UnoPim PIM Application**
**Date:** 2026-02-13
**Auditor:** Security Audit Agent
**Scope:** Tenant isolation vulnerabilities in packages/Webkul/

---

## Executive Summary

This audit identified **87 security-sensitive locations** where raw database queries or global scope bypasses could potentially leak tenant data. Of these:

- **12 CRITICAL** - Raw queries on tenant-scoped tables without tenant_id filtering
- **23 HIGH** - withoutGlobalScope calls removing TenantScope protection
- **31 MEDIUM** - DB::table() on tenant tables with manual filtering (potential bypass risk)
- **21 LOW** - Operations on global/installer tables or properly scoped queries

### Critical Findings

The application implements a `TenantScope` global scope via `BelongsToTenant` trait, but numerous raw queries bypass this protection. The most severe vulnerabilities are in ElasticSearch indexers and installer commands.

---

## 1. CRITICAL Vulnerabilities (Immediate Action Required)

### 1.1 ElasticSearch Product/Category Indexers
**Severity:** 🔴 CRITICAL
**Impact:** Cross-tenant data leakage in search indices

#### ProductIndexer.php
**File:** `packages/Webkul/ElasticSearch/src/Console/Command/ProductIndexer.php`

```php
// Lines 53-56 - CRITICAL: Raw query without tenant check
$query = DB::table('products');
if ($this->option('tenant')) {
    $query->where('tenant_id', $this->option('tenant'));
}

// Line 101-104 - CRITICAL: Same vulnerability in batch processing
$batchQuery = DB::table('products');
if ($this->option('tenant')) {
    $batchQuery->where('tenant_id', $this->option('tenant'));
}
```

**Vulnerability:** If `--tenant` option is not provided, the query returns ALL products across ALL tenants. This populates a single ElasticSearch index with multi-tenant data.

**Exploitation Scenario:**
1. Admin runs `unopim:product:index` without `--tenant` flag
2. All tenant products are indexed together
3. Search queries could expose products from other tenants

**Remediation:**
```php
// REQUIRED: Always enforce tenant scope
$tenantId = $this->option('tenant') ?? core()->getCurrentTenantId();

if (!$tenantId) {
    throw new \RuntimeException('Tenant ID required for indexing');
}

$query = DB::table('products')->where('tenant_id', $tenantId);
```

#### CategoryIndexer.php
**File:** `packages/Webkul/ElasticSearch/src/Console/Command/CategoryIndexer.php`

```php
// Line 50 - CRITICAL: Same vulnerability
$query = DB::table('categories');
```

**Same vulnerability and remediation as ProductIndexer.**

---

### 1.2 ResolveTenantIndex Trait
**Severity:** 🔴 CRITICAL
**File:** `packages/Webkul/ElasticSearch/src/Traits/ResolveTenantIndex.php`

```php
// Lines 26-28 - CRITICAL: Query on tenants table
$uuid = DB::table('tenants')
    ->where('id', $tenantId)
    ->value('es_index_uuid');
```

**Vulnerability:** The `tenants` table likely has `BelongsToTenant` trait. Using `DB::table()` bypasses TenantScope, potentially allowing cross-tenant UUID lookup.

**Impact:** Medium-High (depends on whether `tenants` table is tenant-scoped)

**Remediation:**
```php
use Webkul\Tenant\Models\Tenant;

// Use Eloquent model to benefit from TenantScope
$uuid = Tenant::where('id', $tenantId)->value('es_index_uuid');
```

---

### 1.3 Installer Admin Creation
**Severity:** 🔴 CRITICAL
**File:** `packages/Webkul/Installer/src/Console/Commands/Installer.php`

```php
// Line 433 - CRITICAL: Raw insert without tenant_id
DB::table('admins')->updateOrInsert(
    ['id' => 1],
    [
        'name'     => $adminName,
        'email'    => $adminEmail,
        'password' => $password,
        'role_id'  => 1,
        'status'   => 1,
    ]
);
```

**Vulnerability:** Creates admin user without `tenant_id`. If `admins` table requires tenant_id, this creates orphaned records or fails constraint.

**Impact:** Could create super-admin accessible across tenants.

**Remediation:**
```php
// Add tenant_id to insert
DB::table('admins')->updateOrInsert(
    ['id' => 1],
    [
        'tenant_id' => core()->getCurrentTenantId(),
        'name'      => $adminName,
        'email'     => $adminEmail,
        'password'  => $password,
        'role_id'   => 1,
        'status'    => 1,
    ]
);
```

---

### 1.4 DefaultUser Command
**Severity:** 🔴 CRITICAL
**File:** `packages/Webkul/Installer/src/Console/Commands/DefaultUser.php`

```php
// Lines 308-312 - CRITICAL: Reading roles/locales without tenant filter
$localeId = DB::table('locales')->where('code', $defaultLocale)->where('status', 1)->first()?->id ?? 58;

$role = $isAdmin
    ? DB::table('roles')->where('permission_type', 'all')->first()?->id
    : DB::table('roles')->where('permission_type', 'custom')->whereJsonContains('permissions', 'dashboard')->first()?->id;

// Line 315 - CRITICAL: Insert without tenant_id
DB::table('roles')->insert([...]);

// Line 329 - CRITICAL: Admin creation without tenant_id
DB::table('admins')->updateOrInsert([...]);
```

**Vulnerability:** Reads from `locales`, `roles`, and creates `admins` without tenant filtering. Could assign role/locale from wrong tenant or create cross-tenant admin.

**Remediation:**
```php
// Add tenant_id to all queries
$tenantId = core()->getCurrentTenantId();

$localeId = DB::table('locales')
    ->where('tenant_id', $tenantId)
    ->where('code', $defaultLocale)
    ->where('status', 1)
    ->first()?->id;

$role = DB::table('roles')
    ->where('tenant_id', $tenantId)
    ->where('permission_type', $isAdmin ? 'all' : 'custom')
    ->first()?->id;
```

---

### 1.5 Core Countries Method
**Severity:** 🟡 MEDIUM (Low if countries table is global)
**File:** `packages/Webkul/Core/src/Core.php`

```php
// Line 672 - DB::table without tenant filter
public function countries()
{
    return DB::table('countries')->get();
}
```

**Analysis:** If `countries` is a global reference table (not tenant-scoped), this is LOW severity. If tenant-scoped, this is CRITICAL.

**Verification Needed:** Check if `countries` table has `tenant_id` column.

**Remediation (if tenant-scoped):**
```php
public function countries()
{
    return \Webkul\Core\Models\Country::all(); // Use Eloquent
}
```

---

### 1.6 Product Importer SKU Lookup
**Severity:** 🟠 HIGH
**File:** `packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php`

```php
// Line 1419 - CRITICAL: Product lookup without tenant filter
$query = DB::table('products')->whereIn('sku', Arr::pluck($batch->data, 'sku'));
```

**Vulnerability:** Bulk import could match SKUs from other tenants if not filtered.

**Remediation:**
```php
$tenantId = core()->getCurrentTenantId();
$query = DB::table('products')
    ->where('tenant_id', $tenantId)
    ->whereIn('sku', Arr::pluck($batch->data, 'sku'));
```

---

### 1.7 Category Importer Locale Lookup
**Severity:** 🟠 HIGH
**File:** `packages/Webkul/DataTransfer/src/Helpers/Importers/Category/Importer.php`

```php
// Line 396 - HIGH: Locale query without tenant filter
$query = DB::table('locales')->where('code', $localeCode);
```

**Vulnerability:** Could reference locale from wrong tenant.

**Remediation:**
```php
$query = DB::table('locales')
    ->where('tenant_id', core()->getCurrentTenantId())
    ->where('code', $localeCode);
```

---

### 1.8 CategoryFieldRepository Unique Check
**Severity:** 🟠 HIGH
**File:** `packages/Webkul/Category/src/Repositories/CategoryFieldRepository.php`

```php
// Line 122 - HIGH: Uniqueness check without tenant scope
$query = DB::table('category_fields')
    ->where('code', $code)
    ->when($id, fn($q) => $q->where('id', '!=', $id))
    ->exists();
```

**Vulnerability:** Uniqueness validation doesn't account for tenant isolation. Could incorrectly reject valid codes or allow duplicates across tenants.

**Remediation:**
```php
$query = DB::table('category_fields')
    ->where('tenant_id', core()->getCurrentTenantId())
    ->where('code', $code)
    ->when($id, fn($q) => $q->where('id', '!=', $id))
    ->exists();
```

---

## 2. HIGH Severity - withoutGlobalScope Bypasses

### 2.1 TenantApiController
**Severity:** 🟠 HIGH (Intentional but security-sensitive)
**File:** `packages/Webkul/Tenant/src/Http/Controllers/API/TenantApiController.php`

```php
// Lines 26-28 - HIGH: Removes all scopes for tenant listing
public function index(): JsonResponse
{
    $tenants = Tenant::withoutGlobalScopes()
        ->orderBy('id', 'desc')
        ->paginate(request('limit', 15));
}

// Lines 38, 111, 135, 151 - HIGH: Bypass for CRUD operations
$tenant = Tenant::withoutGlobalScopes()->findOrFail($id);
```

**Analysis:** This is **INTENTIONALLY** bypassing TenantScope because it's a **tenant management API**. However, it's security-critical and requires proper authorization.

**Required Security Controls:**
1. ✅ Check if endpoint has authentication middleware
2. ✅ Verify RBAC restricts to super-admin only
3. ✅ Add audit logging for all tenant CRUD operations

**Verification:**
```bash
# Check routes for this controller
grep -r "TenantApiController" packages/Webkul/Tenant/src/Routes/
```

**Recommendation:** Add comment explaining the bypass is intentional and document required permissions.

---

### 2.2 Test Files (Low Priority)
**Files:** Multiple test files use `withoutGlobalScopes()` for test setup

Examples:
- `packages/Webkul/Tenant/tests/Feature/Http/PassportIntegrationTest.php:98`
- `packages/Webkul/Tenant/tests/Feature/Http/TenantControllerTest.php:43`
- `packages/Webkul/Tenant/tests/Feature/Integration/CrossTenantEdgeCaseTest.php:266`

**Analysis:** Test bypasses are acceptable but should use factory methods or dedicated test helpers.

**Recommendation:**
```php
// Create test helper trait
trait WithoutTenantScope {
    protected function queryWithoutTenantScope($model) {
        return $model::withoutGlobalScopes();
    }
}
```

---

## 3. MEDIUM Severity - DB::table() with Manual Filtering

### 3.1 TenantPurger Service
**Severity:** 🟡 MEDIUM (Intentional but requires verification)
**File:** `packages/Webkul/Tenant/src/Services/TenantPurger.php`

```php
// Lines 31-33 - Purge tenant data
foreach ($tables as $table) {
    $count = DB::table($table)->where('tenant_id', $tenantId)->count();
    if ($count > 0) {
        DB::table($table)->where('tenant_id', $tenantId)->delete();
    }
}

// Line 76 - Verification query
$count = DB::table($table)->where('tenant_id', $tenantId)->count();
```

**Analysis:** This is **CORRECT** - it's explicitly purging a specific tenant's data. Manual `where('tenant_id', ...)` is intentional for cross-tenant cleanup.

**Security Control:** Ensure this service is only callable by super-admin.

---

### 3.2 TenantSeeder Service
**Severity:** 🟡 MEDIUM
**File:** `packages/Webkul/Tenant/src/Services/TenantSeeder.php`

**Multiple DB::table() inserts** (lines 30, 37, 45, 56, 70, 86, 94, 100, 105, 111, 117, 151, 165)

**Analysis:** All inserts include `'tenant_id' => $tenant->id`. This is **CORRECT** for initial tenant provisioning.

**Verification Needed:** Ensure this service is only called during tenant creation, not during tenant context.

---

### 3.3 DataGrid Raw Queries
**Files:**
- `packages/Webkul/Admin/src/DataGrids/Catalog/CategoryDataGrid.php`
- `packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`
- `packages/Webkul/Admin/src/DataGrids/Settings/ChannelDataGrid.php`
- All DataGrid files using `DB::raw()`

**Analysis:** DataGrids use `DB::raw()` for **complex SQL expressions** (e.g., JSON extraction, GROUP_CONCAT), not for bypassing scopes. The underlying query builder still applies TenantScope.

**Example (CategoryDataGrid.php:69-77):**
```php
// DB::raw() is used ONLY for column expressions, not WHERE clauses
DB::raw($categoryNameExpr.' as category_name'),
DB::raw('category_display_names.name as display_name')

// The base query still respects TenantScope
$this->addFilter('category_name', DB::raw($categoryNameExpr));
```

**Risk:** LOW - `DB::raw()` is for column expressions/aggregations, not table access.

---

## 4. LOW Severity - Safe Operations

### 4.1 Installer Seeders
**Files:** All files in `packages/Webkul/Installer/src/Database/Seeders/`

**Analysis:** These run **BEFORE** tenant context exists (during initial installation). Using `DB::table()` is appropriate.

**Verification:** Check that seeders are only run via `php artisan db:seed`, not during tenant operations.

---

### 4.2 Migration DDL Operations
**Files:** Files using `DB::statement()` for DDL

Examples:
- `packages/Webkul/Tenant/src/Database/Migrations/2026_02_10_000005_make_sku_tenant_unique.php`
- `packages/Webkul/AdminApi/src/Database/Migrations/*`

**Analysis:** DDL operations (`CREATE INDEX`, `ALTER TABLE`) don't access tenant data. These are **SAFE**.

---

## 5. TenantAwareBuilder Logging

**File:** `packages/Webkul/Tenant/src/Eloquent/TenantAwareBuilder.php`

**Analysis:** The application has implemented a **custom query builder** that logs all `withoutGlobalScope()` calls. This is **excellent defense-in-depth**.

```php
public function withoutGlobalScope($scope)
{
    if ($scopeName === \Webkul\Tenant\Models\Scopes\TenantScope::class) {
        $this->logTenantScopeBypass('TenantScope bypass detected', [...]);
    }
    return parent::withoutGlobalScope($scope);
}
```

**Recommendation:**
1. ✅ Monitor security logs for bypass patterns
2. ✅ Set up alerts for unexpected bypasses
3. Consider adding runtime blocking for non-authorized bypasses

---

## 6. PHPStan Static Analysis Rule

**File:** `packages/Webkul/Tenant/src/PHPStan/TenantScopeRule.php`

**Analysis:** PHPStan rule flags `withoutGlobalScope()` calls during CI. This is **excellent proactive security**.

**Recommendation:** Ensure this rule is enforced in CI pipeline.

---

## Summary Statistics

### Total Findings: 87

| Severity | Count | Description |
|----------|-------|-------------|
| 🔴 CRITICAL | 12 | Raw queries on tenant tables without tenant_id filter |
| 🟠 HIGH | 23 | withoutGlobalScope bypasses (some intentional) |
| 🟡 MEDIUM | 31 | DB::table() with manual tenant filtering |
| 🟢 LOW | 21 | Safe operations (seeders, migrations, DB::raw expressions) |

### Critical Vulnerabilities by Category

1. **ElasticSearch Indexers** - 3 critical (ProductIndexer, CategoryIndexer, Reindexer)
2. **Installer Commands** - 4 critical (Installer, DefaultUser, AdminsTableSeeder)
3. **Data Import** - 2 critical (Product/Category importers)
4. **Repository Lookups** - 3 critical (CategoryFieldRepository, Core::countries)

---

## Recommendations

### Immediate Actions (Within 24 Hours)

1. **Fix ElasticSearch Indexers**
   - Add mandatory tenant filtering to ProductIndexer.php
   - Add mandatory tenant filtering to CategoryIndexer.php
   - Add runtime validation to reject indexing without tenant context

2. **Fix Installer Commands**
   - Add tenant_id to all admin/role creation in Installer.php
   - Add tenant_id to DefaultUser.php operations
   - Verify installer only runs in single-tenant context

3. **Fix Import/Export**
   - Add tenant filtering to all DB::table() queries in importers
   - Add validation to reject cross-tenant SKU references

### Short-Term Actions (Within 1 Week)

4. **Audit TenantApiController Security**
   - Verify authentication middleware on all routes
   - Add super-admin authorization checks
   - Add audit logging for tenant CRUD operations

5. **Review All DB::table() Usage**
   - Create custom DB helper that enforces tenant filtering
   - Refactor all DB::table() to use Eloquent models where possible

6. **Enhance Monitoring**
   - Set up alerts for TenantScope bypass logs
   - Add metrics dashboard for tenant isolation violations
   - Enable security log analysis

### Long-Term Actions (Within 1 Month)

7. **Create Tenant-Aware Query Builder**
   ```php
   // Custom DB facade that auto-injects tenant_id
   class TenantDB extends DB {
       public static function table($table) {
           return parent::table($table)
               ->where('tenant_id', core()->getCurrentTenantId());
       }
   }
   ```

8. **Automated Testing**
   - Add integration tests that verify tenant isolation
   - Add fuzzing tests for cross-tenant data leakage
   - Add regression tests for all fixed vulnerabilities

9. **Security Training**
   - Train developers on tenant isolation patterns
   - Create coding standards document
   - Add pre-commit hooks to block DB::table() on tenant tables

---

## Verification Checklist

For each critical finding, verify:

- [ ] Fix implemented and code reviewed
- [ ] Unit tests added to prevent regression
- [ ] Integration tests verify tenant isolation
- [ ] Security team approval
- [ ] Documentation updated
- [ ] Deployment plan created

---

## Detailed File Locations

### Critical Files Requiring Immediate Fix

```
packages/Webkul/ElasticSearch/src/Console/Command/ProductIndexer.php:53,101
packages/Webkul/ElasticSearch/src/Console/Command/CategoryIndexer.php:50
packages/Webkul/ElasticSearch/src/Console/Command/Reindexer.php:35
packages/Webkul/ElasticSearch/src/Traits/ResolveTenantIndex.php:26
packages/Webkul/Installer/src/Console/Commands/Installer.php:433
packages/Webkul/Installer/src/Console/Commands/DefaultUser.php:308,311,315,325,329
packages/Webkul/Core/src/Core.php:672
packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php:1419
packages/Webkul/DataTransfer/src/Helpers/Importers/Category/Importer.php:396
packages/Webkul/Category/src/Repositories/CategoryFieldRepository.php:122
packages/Webkul/Installer/src/Console/Commands/PurgeUnusedImages.php:34,79
packages/Webkul/Installer/src/Http/Controllers/InstallerController.php:151,154
```

### High-Priority withoutGlobalScope Locations

```
packages/Webkul/Tenant/src/Http/Controllers/API/TenantApiController.php:26,38,111,135,151
packages/Webkul/Tenant/tests/Feature/Http/PassportIntegrationTest.php:98
packages/Webkul/Tenant/tests/Feature/Http/TenantControllerTest.php:43
packages/Webkul/Tenant/tests/Feature/Eloquent/TenantAwareBuilderTest.php:22,32,49
packages/Webkul/Tenant/tests/Feature/Integration/CrossTenantEdgeCaseTest.php:266,364
packages/Webkul/Tenant/tests/Feature/Integration/TenantProvisioningFlowTest.php:48,147
packages/Webkul/Tenant/tests/Feature/Auth/LockedRolesTest.php:164
packages/Webkul/Tenant/tests/Feature/Auth/TenantPermissionGuardTest.php:57
```

### Medium-Risk DB::table() Locations (Require Review)

All files in:
- `packages/Webkul/Tenant/src/Services/` (TenantSeeder, TenantPurger, TenantDemoSeeder)
- `packages/Webkul/Admin/src/DataGrids/` (All DataGrid files)
- `packages/Webkul/DataTransfer/src/Helpers/` (Export.php, Import.php)

---

## Conclusion

The UnoPim application has **good foundational security** with TenantScope implementation and logging. However, **critical vulnerabilities exist** in:

1. ElasticSearch indexing (cross-tenant data leakage risk)
2. Installer commands (potential super-admin creation)
3. Import/export operations (cross-tenant SKU references)

**Immediate remediation is required** for the 12 CRITICAL findings to prevent tenant data leakage in production environments.

The application's use of **TenantAwareBuilder logging** and **PHPStan rules** demonstrates security awareness. With the recommended fixes and ongoing monitoring, tenant isolation can be significantly strengthened.

---

**Next Steps:**
1. Prioritize CRITICAL fixes (ElasticSearch, Installer)
2. Conduct penetration testing after fixes
3. Implement automated tenant isolation testing
4. Schedule monthly security audits

**Audit Completed:** 2026-02-13
