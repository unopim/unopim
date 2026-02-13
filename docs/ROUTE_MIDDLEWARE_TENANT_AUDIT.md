# Route & Middleware Tenant Isolation Security Audit
**UnoPim PIM Application - Laravel 10**

**Audit Date:** 2026-02-13
**Auditor:** Security Auditor (DevSecOps Specialist)
**Scope:** Route files and middleware chains for tenant isolation gaps

---

## Executive Summary

This comprehensive security audit examined all route definitions, middleware configurations, and tenant isolation mechanisms across the UnoPim application. The audit identified **3 CRITICAL vulnerabilities**, **1 HIGH-risk gap**, and **3 MEDIUM-risk concerns** related to tenant isolation in routing.

### Critical Findings:
1. **API V1 routes missing tenant.token middleware** (CRITICAL)
2. **Webhook routes missing tenant middleware** (CRITICAL)
3. **Integration/API Keys routes missing tenant middleware** (CRITICAL)

### Overall Security Posture: ⚠️ **HIGH RISK**

---

## 1. Middleware Infrastructure

### 1.1 Registered Tenant Middleware

**Location:** `packages/Webkul/Tenant/src/Providers/TenantServiceProvider.php:36-39`

```php
$router->aliasMiddleware('tenant', TenantMiddleware::class);
$router->aliasMiddleware('tenant.safe-errors', TenantSafeErrorHandler::class);
$router->aliasMiddleware('tenant.token', TenantTokenValidator::class);
$router->aliasMiddleware('platform.operator', PlatformOperatorMiddleware::class);
```

**Status:** ✅ Properly registered

### 1.2 HTTP Kernel Configuration

**Location:** `app/Http/Kernel.php`

**Global Middleware:** No tenant middleware (correct - tenant isolation is route-specific)

**Middleware Groups:**
- `web`: Standard session, CSRF, cookies
- `api`: Throttling + route binding

**Status:** ✅ Correct design

---

## 2. Critical Vulnerabilities

### 2.1 🔴 CRITICAL: API V1 Routes Missing tenant.token

**Files Affected:**
- `packages/Webkul/AdminApi/src/Routes/V1/catalog-routes.php`
- `packages/Webkul/AdminApi/src/Routes/V1/settings-routes.php`

**Current Middleware:**
```php
Route::group([
    'middleware' => ['auth:api'],  // ❌ MISSING tenant.token
], function () {
    // All API V1 routes
});
```

**Vulnerable Routes (50+ endpoints):**
- `/v1/rest/attribute-groups/*`
- `/v1/rest/attributes/*`
- `/v1/rest/families/*`
- `/v1/rest/category-fields/*`
- `/v1/rest/categories/*`
- `/v1/rest/media-files/*`
- `/v1/rest/products/*`
- `/v1/rest/configrable-products/*`
- `/v1/rest/locales/*`
- `/v1/rest/channels/*`
- `/v1/rest/currencies/*`

**Root Cause:**
The parent route file (`admin-api.php`) correctly includes `tenant.token` middleware:

```php
// admin-api.php (PARENT)
Route::group([
    'middleware' => ['auth:api', 'tenant.token', 'tenant.safe-errors', 'api.scope', ...],
], function () {
    require 'V1/settings-routes.php';
    require 'V1/catalog-routes.php';
});
```

However, child routes REDEFINE a new group with only `auth:api`, which **OVERRIDES** the parent middleware instead of inheriting it.

**Impact:**
- Orphan tokens (from deleted tenants) can access the API
- No tenant context is set for API requests
- Cross-tenant data leakage if controllers don't manually scope queries
- Suspended tenant tokens remain valid

**Attack Scenario:**
1. Attacker obtains OAuth token for Tenant A
2. Tenant A is suspended/deleted
3. Token remains valid (no `tenant.token` validation)
4. Attacker accesses `/v1/rest/products` and receives unscoped data

**Severity:** 🔴 **CRITICAL**

**Fix:**
```php
// Option 1: Remove the redundant Route::group() wrapper in child files
// V1/catalog-routes.php
Route::controller(AttributeGroupController::class)->prefix('attribute-groups')->group(function () {
    // Routes here inherit parent middleware
});

// Option 2: Explicitly merge middleware
Route::middleware(['tenant.token'])->group(function () {
    // Additional routes
});
```

---

### 2.2 🔴 CRITICAL: Webhook Routes Missing tenant

**File:** `packages/Webkul/Webhook/src/Routes/web.php:10`

**Current Middleware:**
```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
```

**Vulnerable Routes:**
- `GET /admin/webhook/settings`
- `POST /admin/webhook/settings`
- `GET /admin/webhook/settings/form-data`
- `GET /admin/webhook/logs`
- `DELETE /admin/webhook/logs/delete/{id}`
- `POST /admin/webhook/logs/mass-delete`

**Impact:**
- Webhook settings may be visible across tenants
- Webhook logs could leak cross-tenant event data
- Webhook configurations may not be tenant-isolated

**Attack Scenario:**
1. Tenant A admin accesses `/admin/webhook/settings`
2. If `WebhookSetting::all()` query isn't manually scoped, admin sees all tenants' webhooks
3. Admin modifies another tenant's webhook URL to intercept their data

**Severity:** 🔴 **CRITICAL**

**Fix:**
```php
Route::group(['middleware' => ['admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
```

---

### 2.3 🔴 CRITICAL: Integration/API Keys Routes Missing tenant

**File:** `packages/Webkul/AdminApi/src/Routes/integrations-routes.php:9`

**Current Middleware:**
```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
```

**Vulnerable Routes:**
- `GET /admin/integrations/api-keys`
- `GET /admin/integrations/api-keys/create`
- `POST /admin/integrations/api-keys/create`
- `GET /admin/integrations/api-keys/edit/{id}`
- `PUT /admin/integrations/api-keys/edit/{id}`
- `DELETE /admin/integrations/api-keys/edit/{id}`
- `POST /admin/integrations/api-keys/generate`
- `POST /admin/integrations/api-keys/re-generate-secrete`

**Impact:**
- API keys may be exposed across tenants
- Key generation may not be tenant-scoped
- Cross-tenant API key access could compromise tenant data

**Attack Scenario:**
1. Tenant A admin accesses `/admin/integrations/api-keys`
2. If query isn't scoped to `tenant_id`, admin sees keys from other tenants
3. Admin copies another tenant's API key and accesses their data

**Severity:** 🔴 **CRITICAL**

**Fix:**
```php
Route::group(['middleware' => ['admin', 'tenant'], 'prefix' => config('app.admin_url')], function () {
```

---

## 3. High-Risk Issues

### 3.1 🟠 HIGH: Tenant API Routes Lack Controller-Level Validation

**File:** `packages/Webkul/Tenant/src/Routes/api-routes.php:6-9`

**Current Middleware:**
```php
Route::group([
    'middleware' => ['auth:api', 'tenant.token', 'tenant.safe-errors'],
    'prefix' => 'api/v1/tenants',
], function () {
```

**Routes:**
- `GET /api/v1/tenants/`
- `GET /api/v1/tenants/{id}`
- `POST /api/v1/tenants/`
- `PUT /api/v1/tenants/{id}`
- `DELETE /api/v1/tenants/{id}`
- `POST /api/v1/tenants/{id}/suspend`
- `POST /api/v1/tenants/{id}/activate`

**Issue:**
While the routes have `tenant.token` middleware, the controller methods (`TenantApiController`) do NOT verify that a tenant user can only access their OWN tenant.

**Expected Behavior:**
- Platform operators (tenant_id = null) can access ANY tenant
- Tenant users (tenant_id = 123) can ONLY access tenant 123

**Current Vulnerability:**
```php
// TenantApiController.php
public function show($id)
{
    // ❌ NO CHECK: Tenant user with tenant_id=1 can access /api/v1/tenants/2
    return Tenant::withoutGlobalScopes()->findOrFail($id);
}
```

**Severity:** 🟠 **HIGH**

**Fix:**
```php
public function show($id)
{
    $user = auth()->guard('api')->user();

    // Platform operators can view any tenant
    if (is_null($user->tenant_id)) {
        return Tenant::withoutGlobalScopes()->findOrFail($id);
    }

    // Tenant users can only view their own tenant
    if ($user->tenant_id !== (int)$id) {
        abort(403, 'You can only access your own tenant.');
    }

    return Tenant::findOrFail($id);
}
```

---

## 4. Medium-Risk Concerns

### 4.1 🟡 MEDIUM: TenantMiddleware Allows Requests Without Context

**File:** `packages/Webkul/Tenant/src/Http/Middleware/TenantMiddleware.php:44-47`

```php
// No tenant resolved — let the request proceed without tenant context.
// Routes that truly require a tenant should guard this themselves
// or the caller should use the "tenant.required" middleware variant.
return $next($request);
```

**Issue:**
If tenant resolution fails (no subdomain, no header, no token, no session), the request proceeds without tenant context. Controllers relying on `core()->getCurrentTenantId()` may return unscoped data.

**Risk:**
Developer error in controllers that forget to check tenant context could lead to cross-tenant data leakage.

**Severity:** 🟡 **MEDIUM**

**Recommended Enhancement:**
```php
// Create tenant.required middleware variant
class TenantRequiredMiddleware extends TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            abort(403, 'Tenant context is required for this route.');
        }

        return parent::handle($request, $next);
    }
}
```

---

### 4.2 🟡 MEDIUM: No Global Tenant Scoping on Models

**Issue:**
Route model binding (e.g., `Route::get('/products/{id}')`) does NOT automatically scope by tenant. Controllers must manually filter queries.

**Risk:**
Developer error (forgetting `where('tenant_id', ...)`) could expose cross-tenant data.

**Example Vulnerable Code:**
```php
// In controller
public function edit($id)
{
    // ❌ NO TENANT SCOPE - could return product from another tenant
    $product = Product::findOrFail($id);
}
```

**Expected Secure Code:**
```php
public function edit($id)
{
    // ✅ Manually scoped
    $product = Product::where('tenant_id', core()->getCurrentTenantId())
        ->findOrFail($id);
}
```

**Severity:** 🟡 **MEDIUM**

**Recommended Fix:**
Implement Eloquent global scopes on all tenant-scoped models:

```php
// In Product model (and all tenant-scoped models)
protected static function booted()
{
    static::addGlobalScope('tenant', function (Builder $builder) {
        $tenantId = core()->getCurrentTenantId();
        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }
    });
}
```

---

### 4.3 🟡 MEDIUM: No Rate Limiting on Web Routes

**File:** `app/Http/Kernel.php:31-39`

**Current Configuration:**
```php
'web' => [
    // ... no throttle middleware
],

'api' => [
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
],
```

**Issue:**
Login routes (`/admin/login`) are not rate-limited, allowing brute-force attacks.

**Severity:** 🟡 **MEDIUM**

**Fix:**
```php
'web' => [
    // ... existing middleware
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':web',
],
```

---

## 5. Secure Route Configurations

### 5.1 ✅ Admin Web Routes

**Files:** All properly secured with `['admin', 'tenant']` middleware:
- `packages/Webkul/Admin/src/Routes/catalog-routes.php`
- `packages/Webkul/Admin/src/Routes/settings-routes.php`
- `packages/Webkul/Admin/src/Routes/configuration-routes.php`
- `packages/Webkul/Admin/src/Routes/notification-routes.php`
- `packages/Webkul/Admin/src/Routes/rest-routes.php`
- `packages/Webkul/Completeness/src/Routes/web.php`

**Status:** ✅ **SECURE**

---

### 5.2 ✅ Auth Routes

**File:** `packages/Webkul/Admin/src/Routes/auth-routes.php`

**Middleware:** NONE (public routes for login/logout)

**Status:** ✅ **SECURE** - Correct design (authentication happens before tenant context)

---

### 5.3 ✅ Tenant Management Routes (Web)

**File:** `packages/Webkul/Tenant/src/Routes/admin-routes.php`

**Middleware:** `['web', 'admin', 'platform.operator']`

**Status:** ✅ **SECURE** - Only platform operators can manage tenants

---

### 5.4 ✅ Installer Routes

**File:** `packages/Webkul/Installer/src/Routes/web.php`

**Middleware:** `['web', 'installer_locale']`

**Status:** ✅ **SECURE** - Runs before tenant setup

---

## 6. Middleware Logic Analysis

### 6.1 TenantMiddleware (Session/Web)

**File:** `packages/Webkul/Tenant/src/Http/Middleware/TenantMiddleware.php`

**Tenant Resolution Priority:**
1. Subdomain extraction (`tenant-a.app.example.com`)
2. `X-Tenant-ID` header (validated against user)
3. OAuth token → admin → tenant_id
4. Session context (platform operator switching)

**Security Features:**
- ✅ Rejects suspended/deleted tenants (503 error)
- ✅ Sets tenant context via `core()->setCurrentTenantId()`
- ✅ Validates X-Tenant-ID against authenticated user
- ✅ Platform operators can switch context
- ✅ Session context only for platform operators

**Status:** ✅ **SECURE**

---

### 6.2 TenantTokenValidator (API)

**File:** `packages/Webkul/Tenant/src/Http/Middleware/TenantTokenValidator.php`

**Security Features:**
- ✅ Rejects orphan tokens (tenant deleted)
- ✅ Rejects suspended tenant tokens (403 error)
- ✅ Sets tenant context via `core()->setCurrentTenantId()`
- ✅ Logs platform operator API access
- ✅ Platform users bypass validation (tenant_id = null)

**Status:** ✅ **SECURE** (but NOT applied to V1 API routes - see Critical #1)

---

### 6.3 PlatformOperatorMiddleware

**File:** `packages/Webkul/Tenant/src/Http/Middleware/PlatformOperatorMiddleware.php`

**Security Features:**
- ✅ Only allows users with tenant_id = null
- ✅ Returns JSON 403 for API requests
- ✅ Aborts with 403 for web requests

**Status:** ✅ **SECURE**

---

## 7. Summary of Findings

### Critical Vulnerabilities

| # | Vulnerability | File | Severity | Routes Affected |
|---|--------------|------|----------|-----------------|
| 1 | API V1 routes missing tenant.token | `V1/catalog-routes.php`, `V1/settings-routes.php` | 🔴 CRITICAL | 50+ API endpoints |
| 2 | Webhook routes missing tenant | `Webhook/src/Routes/web.php` | 🔴 CRITICAL | 6 endpoints |
| 3 | Integration routes missing tenant | `AdminApi/src/Routes/integrations-routes.php` | 🔴 CRITICAL | 8 endpoints |

### High-Risk Issues

| # | Issue | File | Severity | Impact |
|---|-------|------|----------|---------|
| 4 | Tenant API lacks controller validation | `Tenant/src/Routes/api-routes.php` | 🟠 HIGH | Cross-tenant access |

### Medium-Risk Concerns

| # | Concern | File | Severity | Impact |
|---|---------|------|----------|---------|
| 5 | Middleware allows no-tenant requests | `TenantMiddleware.php:47` | 🟡 MEDIUM | Developer error risk |
| 6 | No global tenant scoping on models | All models | 🟡 MEDIUM | Route binding bypass |
| 7 | No rate limiting on web routes | `app/Http/Kernel.php` | 🟡 MEDIUM | Brute-force attacks |

---

## 8. Remediation Roadmap

### Phase 1: CRITICAL (Days 1-2)

**1. Fix API V1 Routes:**
```php
// Remove redundant Route::group in V1/*.php files
// Rely on parent middleware from admin-api.php
```

**2. Fix Webhook Routes:**
```php
// web.php:10
Route::group(['middleware' => ['admin', 'tenant'], ...
```

**3. Fix Integration Routes:**
```php
// integrations-routes.php:9
Route::group(['middleware' => ['admin', 'tenant'], ...
```

**Testing:**
- Verify orphan tokens are rejected
- Verify webhook settings are tenant-scoped
- Verify API key management is tenant-scoped

---

### Phase 2: HIGH (Days 3-5)

**4. Add Controller Validation to Tenant API:**
```php
// TenantApiController.php
public function show($id) {
    $user = auth()->guard('api')->user();
    if (!is_null($user->tenant_id) && $user->tenant_id !== (int)$id) {
        abort(403);
    }
    return Tenant::withoutGlobalScopes()->findOrFail($id);
}
```

---

### Phase 3: MEDIUM (Days 6-10)

**5. Implement Global Tenant Scoping:**
```php
// Create TenantScoped trait
trait TenantScoped {
    protected static function bootTenantScoped() {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = core()->getCurrentTenantId();
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });
    }
}

// Apply to all models: Product, Category, Attribute, etc.
```

**6. Create tenant.required Middleware:**
```php
class TenantRequiredMiddleware extends TenantMiddleware {
    // Abort if no tenant context
}
```

**7. Add Rate Limiting:**
```php
// Kernel.php
'web' => [
    // ...
    ThrottleRequests::class.':web',
],
```

---

### Phase 4: VALIDATION (Days 11-14)

**8. Penetration Testing:**
- Test cross-tenant data access
- Test orphan token scenarios
- Test platform operator privilege escalation

**9. Create Security Tests:**
```php
test('tenant A cannot access tenant B products via API')
test('orphan tokens are rejected')
test('webhook settings are tenant-scoped')
test('api keys are tenant-scoped')
```

---

## 9. Compliance Impact

### GDPR (EU):
- ⚠️ Current API gaps violate Article 32 (security of processing)
- ⚠️ Cross-tenant data leakage violates Article 5 (data minimization)

### SOC 2 Type II:
- ⚠️ Violates CC6.1 (logical access controls)
- ⚠️ Violates CC6.6 (restricted access to data)

### ISO 27001:
- ⚠️ Violates A.9.4.1 (information access restriction)
- ⚠️ Violates A.9.2.3 (management of privileged access rights)

---

## 10. Conclusion

### Risk Assessment: ⚠️ **HIGH RISK**

The UnoPim application has a **well-architected tenant isolation system** with sophisticated middleware for multi-strategy tenant resolution. However, **critical implementation gaps** in API routes and webhook/integration routes create **immediate security risks**.

### Immediate Action Required:

**Deploy Phase 1 fixes within 24-48 hours** to prevent:
- Cross-tenant data access via API
- Orphan token exploitation
- Webhook data leakage
- API key compromise

### Estimated Effort:
- **Phase 1 (CRITICAL):** 8-12 hours
- **Phase 2 (HIGH):** 12-16 hours
- **Phase 3 (MEDIUM):** 16-20 hours
- **Phase 4 (VALIDATION):** 16-20 hours
- **Total:** 7-10 business days

---

## Appendix: Route Middleware Matrix

| Route Group | File | Current Middleware | Required | Status |
|-------------|------|-------------------|----------|--------|
| Admin Catalog | catalog-routes.php | admin, tenant | admin, tenant | ✅ SECURE |
| Admin Settings | settings-routes.php | admin, tenant | admin, tenant | ✅ SECURE |
| Admin Config | configuration-routes.php | admin, tenant | admin, tenant | ✅ SECURE |
| Admin Notifications | notification-routes.php | admin, tenant | admin, tenant | ✅ SECURE |
| Admin Rest | rest-routes.php | admin, tenant | admin, tenant | ✅ SECURE |
| Completeness | web.php | web, admin, tenant | web, admin, tenant | ✅ SECURE |
| Auth | auth-routes.php | NONE | NONE | ✅ SECURE |
| API V1 Catalog | V1/catalog-routes.php | auth:api | auth:api, tenant.token | 🔴 CRITICAL |
| API V1 Settings | V1/settings-routes.php | auth:api | auth:api, tenant.token | 🔴 CRITICAL |
| Webhooks | web.php | admin | admin, tenant | 🔴 CRITICAL |
| Integrations | integrations-routes.php | admin | admin, tenant | 🔴 CRITICAL |
| Tenant Admin | admin-routes.php | web, admin, platform.operator | web, admin, platform.operator | ✅ SECURE |
| Tenant API | api-routes.php | auth:api, tenant.token | auth:api, tenant.token + controller check | 🟠 HIGH |
| Installer | web.php | web, installer_locale | web, installer_locale | ✅ SECURE |

---

**Report Complete**

**Next Actions:**
1. Implement Phase 1 fixes immediately
2. Schedule security review meeting
3. Create JIRA tickets for all phases
4. Update security documentation
5. Schedule follow-up audit in 30 days

**Auditor:** Security Auditor (DevSecOps)
**Date:** 2026-02-13
**Classification:** CONFIDENTIAL
