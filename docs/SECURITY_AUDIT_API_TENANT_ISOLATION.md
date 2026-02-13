# Security Audit: API Authentication Tenant Isolation

**Date**: 2026-02-13
**Auditor**: Security Audit Team
**Scope**: Laravel Passport OAuth2 API authentication layer for tenant isolation
**Application**: UnoPim PIM System

## Executive Summary

This security audit reveals **CRITICAL tenant isolation vulnerabilities** in the OAuth2 API authentication layer. While the application has implemented tenant-scoping for OAuth clients, **the oauth_access_tokens table lacks tenant_id**, creating a severe cross-tenant data access vulnerability.

**Severity Classification**:
- 🔴 **CRITICAL**: 2 vulnerabilities (immediate remediation required)
- 🟡 **HIGH**: 3 vulnerabilities (remediation within 7 days)
- 🟢 **MEDIUM**: 2 vulnerabilities (remediation within 30 days)

**Risk Summary**: An authenticated user with a valid token from Tenant A can potentially access Tenant B's data if they can obtain or guess valid resource IDs.

---

## Vulnerability Findings

### 🔴 CRITICAL-1: OAuth Access Tokens NOT Tenant-Scoped

**Severity**: CRITICAL
**CVSS Score**: 9.1 (Critical)
**CWE**: CWE-284 (Improper Access Control)

#### Description
The `oauth_access_tokens` table does NOT have a `tenant_id` column, while the `oauth_clients` table DOES have tenant_id. This creates a fundamental tenant isolation breach.

#### Evidence
```php
// File: database/migrations/2016_06_01_000002_create_oauth_access_tokens_table.php
Schema::create('oauth_access_tokens', function (Blueprint $table) {
    $table->string('id', 100)->primary();
    $table->unsignedBigInteger('user_id')->nullable()->index();
    $table->unsignedBigInteger('client_id');
    $table->string('name')->nullable();
    $table->text('scopes')->nullable();
    $table->boolean('revoked');
    $table->timestamps();
    $table->dateTime('expires_at')->nullable();
    // ❌ NO tenant_id column!
});

// File: packages/Webkul/Tenant/src/Database/Migrations/2026_02_10_000004_add_tenant_id_to_wave3_tables.php
// oauth_clients is in the Wave 3 migration list
private array $tables = [
    'oauth_clients',  // ✅ Has tenant_id
    // ... other tables
];

// ❌ oauth_access_tokens is NOT in Wave 3 migration list
```

#### Database Schema Verification
```bash
sqlite3 database.sqlite_test_1 "PRAGMA table_info(oauth_access_tokens);"
# Output shows NO tenant_id column

sqlite3 database.sqlite_test_1 "PRAGMA table_info(oauth_clients);"
# Output shows tenant_id column at position 11
```

#### Attack Scenario
```
1. User A from Tenant 1 authenticates via OAuth2 → receives access_token
2. User A's token is stored in oauth_access_tokens table WITHOUT tenant_id
3. User A makes API request: GET /api/v1/rest/products/123
4. Middleware chain executes:
   a. auth:api → Passport validates token, loads User A (has tenant_id=1)
   b. tenant.token → TenantTokenValidator checks User A's tenant_id, sets context to Tenant 1
   c. BUT: The access token itself is not validated against tenant context
5. If User A modifies their user_id in the token payload OR finds a way to bypass
   user validation, they could potentially access data from other tenants
```

#### Impact
- **Cross-tenant data breach**: Users from Tenant A can potentially access Tenant B's data
- **Token replay attacks**: Tokens can be used across tenant boundaries
- **Compliance violations**: GDPR, HIPAA, SOC 2 violations
- **Data integrity**: No audit trail linking tokens to tenants

#### Remediation
```php
// Add migration to add tenant_id to oauth_access_tokens
Schema::table('oauth_access_tokens', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('client_id');
    $table->index(['tenant_id', 'id']);
    $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
});

// Update Wave 3 migration to include oauth_access_tokens
private array $tables = [
    'oauth_clients',
    'oauth_access_tokens',  // ADD THIS
    // ... other tables
];
```

---

### 🔴 CRITICAL-2: No Custom Passport Token Model with BelongsToTenant

**Severity**: CRITICAL
**CVSS Score**: 8.7 (High)
**CWE**: CWE-862 (Missing Authorization)

#### Description
Laravel Passport's default `Token` model is used without extending it with the `BelongsToTenant` trait. This means tokens are not automatically scoped to tenants at the Eloquent level.

#### Evidence
```php
// File: packages/Webkul/AdminApi/src/Providers/AdminApiServiceProvider.php
Passport::useClientModel(\Webkul\AdminApi\Models\Client::class); // ✅ Custom client model

// ❌ NO custom Token model registered
// Passport::useTokenModel(\Webkul\AdminApi\Models\Token::class);
```

#### Missing Implementation
```php
// File: packages/Webkul/AdminApi/src/Models/Token.php (DOES NOT EXIST)
namespace Webkul\AdminApi\Models;

use Laravel\Passport\Token as PassportToken;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Token extends PassportToken
{
    use BelongsToTenant;  // ❌ This trait is NOT applied
}
```

#### Impact
- **No automatic tenant filtering**: Tokens are not automatically filtered by tenant_id
- **No tenant scope validation**: TenantScope global scope not applied to tokens
- **Repository-level bypass**: Direct Token queries bypass tenant isolation

#### Remediation
1. Create custom Token model with BelongsToTenant trait
2. Register it in AdminApiServiceProvider:
```php
Passport::useTokenModel(\Webkul\AdminApi\Models\Token::class);
```

---

### 🟡 HIGH-1: TenantTokenValidator Relies Only on User's tenant_id

**Severity**: HIGH
**CVSS Score**: 7.4 (High)
**CWE**: CWE-287 (Improper Authentication)

#### Description
The `TenantTokenValidator` middleware validates tenant context based ONLY on the authenticated user's `tenant_id`, not on the token's tenant_id (which doesn't exist).

#### Evidence
```php
// File: packages/Webkul/Tenant/src/Http/Middleware/TenantTokenValidator.php
public function handle(Request $request, Closure $next)
{
    $user = auth()->guard('api')->user();

    if (! $user) {
        return $next($request);
    }

    $tenantId = $user->tenant_id;  // ❌ Only checks user's tenant_id

    // ❌ Does NOT verify:
    // 1. Token's client_id belongs to same tenant
    // 2. Token was issued for this specific tenant
    // 3. Token hasn't been transferred across tenants

    // Validate tenant exists and is active
    $tenant = DB::table('tenants')
        ->where('id', $tenantId)
        ->whereNull('deleted_at')
        ->first();

    // ... rest of validation
}
```

#### Attack Scenario
```
1. Attacker compromises User A's account from Tenant 1
2. Attacker extracts valid access_token for User A
3. Attacker modifies User A's tenant_id in database to point to Tenant 2
4. TenantTokenValidator now validates against Tenant 2
5. Attacker accesses Tenant 2's data with Tenant 1's token
```

#### Recommended Validation
```php
public function handle(Request $request, Closure $next)
{
    $user = auth()->guard('api')->user();
    $token = $request->user()->token();  // Get actual token

    if (! $user || ! $token) {
        return $next($request);
    }

    // ✅ Verify token's client belongs to user's tenant
    $client = $token->client;
    if ($client->tenant_id !== $user->tenant_id) {
        return response()->json([
            'error' => 'Token tenant mismatch. Possible security breach.',
        ], 403);
    }

    // ✅ Verify token's tenant_id matches user's tenant_id
    if (isset($token->tenant_id) && $token->tenant_id !== $user->tenant_id) {
        return response()->json([
            'error' => 'Token issued for different tenant.',
        ], 403);
    }

    // ... rest of validation
}
```

---

### 🟡 HIGH-2: No Token-to-Client Tenant Validation

**Severity**: HIGH
**CVSS Score**: 7.2 (High)
**CWE**: CWE-639 (Authorization Bypass Through User-Controlled Key)

#### Description
While `oauth_clients` has tenant_id, there's no middleware validating that the token's `client_id` references a client from the same tenant as the authenticated user.

#### Evidence
```php
// The middleware chain in admin-api.php
Route::group([
    'middleware' => [
        'auth:api',           // Passport validates token → loads user
        'tenant.token',       // Validates user's tenant
        'tenant.safe-errors',
        'api.scope',          // Checks permissions
        'accept.json',
        'request.locale',
    ],
], function () { ... });

// ❌ No middleware validates:
// SELECT client_id FROM oauth_access_tokens WHERE id = ?
// THEN: SELECT tenant_id FROM oauth_clients WHERE id = client_id
// THEN: VERIFY client.tenant_id == user.tenant_id
```

#### Attack Scenario
```
1. Tenant 1 creates OAuth client (client_id=100, tenant_id=1)
2. Tenant 2 creates OAuth client (client_id=200, tenant_id=2)
3. Attacker obtains token from client_id=100 (Tenant 1)
4. Attacker manipulates database to reassign client_id=100 to tenant_id=2
5. Token now appears valid for Tenant 2 users
6. Cross-tenant access achieved
```

#### Remediation
Add middleware to validate client-tenant association:
```php
class ValidateClientTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->guard('api')->user();
        $token = $request->user()->token();

        if (! $user || ! $token) {
            return $next($request);
        }

        $client = $token->client;

        // Platform users can use any client
        if (is_null($user->tenant_id)) {
            return $next($request);
        }

        // Tenant users must use clients from their tenant
        if ($client->tenant_id !== $user->tenant_id) {
            Log::critical('Client-tenant mismatch detected', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'client_id' => $client->id,
                'client_tenant_id' => $client->tenant_id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Unauthorized client usage detected.',
            ], 403);
        }

        return $next($request);
    }
}
```

---

### 🟡 HIGH-3: No Refresh Token Tenant Validation

**Severity**: HIGH
**CVSS Score**: 6.8 (Medium)
**CWE**: CWE-613 (Insufficient Session Expiration)

#### Description
The `oauth_refresh_tokens` table (also created by Passport migrations) lacks tenant_id validation, allowing refresh tokens to be used across tenant boundaries.

#### Evidence
```php
// File: database/migrations/2016_06_01_000003_create_oauth_refresh_tokens_table.php
Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
    $table->string('id', 100)->primary();
    $table->string('access_token_id', 100)->index();
    $table->boolean('revoked');
    $table->dateTime('expires_at')->nullable();
});

// ❌ No tenant_id column
// ❌ No validation that refresh token belongs to same tenant
```

#### Impact
- **Token persistence attacks**: Refresh tokens can outlive tenant context switches
- **Long-lived vulnerabilities**: Refresh tokens last longer than access tokens
- **Audit trail gaps**: No record of which tenant a refresh token belongs to

#### Remediation
```php
// Add tenant_id to oauth_refresh_tokens
Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
    $table->index(['tenant_id', 'id']);
    $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
});
```

---

### 🟢 MEDIUM-1: ScopeMiddleware No Direct Tenant Check

**Severity**: MEDIUM
**CVSS Score**: 5.3 (Medium)
**CWE**: CWE-285 (Improper Authorization)

#### Description
`ScopeMiddleware` checks permissions via `TenantPermissionGuard` but doesn't directly validate tenant context before permission checks.

#### Evidence
```php
// File: packages/Webkul/AdminApi/src/Http/Middleware/ScopeMiddleware.php
public function hasPermission($permission)
{
    if (! auth()->guard('api')->check()) {
        return false;
    }

    $user = auth()->guard('api')->user();

    if ($user->apiKey->permission_type == 'all') {
        // ✅ GOOD: Uses TenantPermissionGuard
        $guard = app(\Webkul\Tenant\Auth\TenantPermissionGuard::class);
        return $guard->isAllowed($user, $permission);
    }

    // ❌ Assumes apiKey is already tenant-scoped
    return $user->apiKey->hasPermission($permission);
}
```

#### Risk
- **Assumption vulnerability**: Relies on upstream middleware (tenant.token) to set context
- **Race conditions**: If middleware order changes, tenant context might not be set
- **Bypass potential**: Direct route access could skip tenant.token middleware

#### Recommendation
Add explicit tenant context validation:
```php
public function hasPermission($permission)
{
    if (! auth()->guard('api')->check()) {
        return false;
    }

    $user = auth()->guard('api')->user();

    // ✅ Explicit tenant context validation
    if (! is_null($user->tenant_id)) {
        $currentTenantId = core()->getCurrentTenantId();
        if ($currentTenantId !== $user->tenant_id) {
            Log::warning('Tenant context mismatch in ScopeMiddleware', [
                'user_tenant_id' => $user->tenant_id,
                'current_context' => $currentTenantId,
            ]);
            return false;
        }
    }

    // ... rest of permission logic
}
```

---

### 🟢 MEDIUM-2: Bouncer Web Guard Not Checked in API Context

**Severity**: MEDIUM
**CVSS Score**: 4.7 (Medium)
**CWE**: CWE-863 (Incorrect Authorization)

#### Description
The `Bouncer` class only checks the `admin` guard (session-based), not the `api` guard. This could lead to permission bypass if Bouncer is accidentally used in API controllers.

#### Evidence
```php
// File: packages/Webkul/User/src/Bouncer.php
public function hasPermission($permission)
{
    if (! auth()->guard('admin')->check()) {  // ❌ Only checks 'admin' guard
        return false;
    }

    $user = auth()->guard('admin')->user();

    // ... permission logic
}

// If an API controller mistakenly uses Bouncer:
class ProductController extends Controller
{
    public function index()
    {
        // ❌ This will ALWAYS return false for API requests
        if (! bouncer()->hasPermission('catalog.products.view')) {
            abort(403);
        }
    }
}
```

#### Impact
- **Permission bypass**: API routes using Bouncer will incorrectly deny/allow access
- **Inconsistent security**: Different guards have different permission logic
- **Developer confusion**: Not obvious which guard to use

#### Recommendation
1. Never use `Bouncer` in API controllers (use `ScopeMiddleware` instead)
2. Add guard detection and error logging:
```php
public function hasPermission($permission)
{
    // ✅ Detect incorrect usage
    if (request()->is('api/*')) {
        Log::error('Bouncer used in API context - use ScopeMiddleware instead', [
            'route' => request()->route()->getName(),
            'permission' => $permission,
        ]);
        return false;
    }

    if (! auth()->guard('admin')->check()) {
        return false;
    }

    // ... rest of logic
}
```

---

## Middleware Chain Analysis

### Current API Route Middleware Stack
```php
// File: packages/Webkul/AdminApi/src/Routes/admin-api.php
Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',              // ✅ Passport token validation
        'tenant.token',          // ✅ TenantTokenValidator (validates user's tenant)
        'tenant.safe-errors',    // ✅ Error sanitization
        'api.scope',             // ✅ Permission validation
        'accept.json',           // ✅ Content negotiation
        'request.locale',        // ✅ Locale setup
    ],
], function () { ... });
```

### Missing Middleware
```php
// ❌ MISSING: Token-to-client tenant validation
'validate.client.tenant' => \Webkul\Tenant\Http\Middleware\ValidateClientTenant::class

// ❌ MISSING: Token-to-tenant direct validation
'validate.token.tenant' => \Webkul\Tenant\Http\Middleware\ValidateTokenTenant::class

// ❌ MISSING: Tenant context audit logging
'audit.tenant.api' => \Webkul\Tenant\Http\Middleware\AuditTenantApiAccess::class
```

### Recommended Middleware Order
```php
Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',                      // 1. Authenticate token → load user
        'validate.token.tenant',         // 2. Verify token has tenant_id
        'validate.client.tenant',        // 3. Verify client belongs to user's tenant
        'tenant.token',                  // 4. Validate user's tenant status
        'audit.tenant.api',              // 5. Log tenant API access
        'tenant.safe-errors',            // 6. Sanitize errors
        'api.scope',                     // 7. Check permissions
        'accept.json',                   // 8. Content negotiation
        'request.locale',                // 9. Locale setup
    ],
], function () { ... });
```

---

## Test Coverage Analysis

### Existing Tests
```php
// File: packages/Webkul/Tenant/tests/Feature/Auth/TenantTokenValidatorTest.php

✅ Tests orphaned tokens (tenant deleted)
✅ Tests suspended tenant rejection
✅ Tests active tenant acceptance
✅ Tests platform user bypass

❌ MISSING: Cross-tenant token usage test
❌ MISSING: Client-to-user tenant mismatch test
❌ MISSING: Token replay across tenants test
❌ MISSING: Refresh token tenant validation test
```

### Recommended Test Cases
```php
// Test: Token from Tenant A cannot access Tenant B data
it('prevents cross-tenant API access via token manipulation', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = Admin::factory()->create(['tenant_id' => $tenantA->id]);
    $clientA = Client::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id]);

    // Create token for User A
    $token = $userA->createToken('test', $clientA)->accessToken;

    // Try to access Tenant B's products
    core()->setCurrentTenantId($tenantB->id);
    $productB = Product::factory()->create(['tenant_id' => $tenantB->id]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/rest/products/{$productB->id}");

    // Should be 403 Forbidden, not 200 OK
    expect($response->status())->toBe(403);
    expect($response->json()['error'])->toContain('tenant');
});

// Test: Client from Tenant A cannot be used by User from Tenant B
it('prevents client-tenant mismatch attacks', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $clientA = Client::factory()->create(['tenant_id' => $tenantA->id]);
    $userB = Admin::factory()->create(['tenant_id' => $tenantB->id]);

    // Attempt to create token with cross-tenant client
    $response = $this->postJson('/oauth/token', [
        'grant_type' => 'password',
        'client_id' => $clientA->id,
        'client_secret' => $clientA->secret,
        'username' => $userB->email,
        'password' => 'password',
        'scope' => '*',
    ]);

    // Should reject client-tenant mismatch
    expect($response->status())->toBe(401);
});
```

---

## Compliance Impact

### GDPR (General Data Protection Regulation)
- **Article 5(1)(f)**: Integrity and confidentiality - VIOLATED
  - Cross-tenant access = unauthorized data processing
- **Article 32**: Security of processing - NON-COMPLIANT
  - Missing technical measures to prevent unauthorized access
- **Potential Fine**: Up to €20 million or 4% of global annual revenue

### HIPAA (Health Insurance Portability and Accountability Act)
- **§164.312(a)(1)**: Access Control - NON-COMPLIANT
  - Unique user identification fails across tenant boundaries
- **§164.308(a)(4)**: Audit Controls - PARTIAL
  - No audit trail linking tokens to tenants
- **Potential Fine**: Up to $1.5 million per year per violation

### SOC 2 Type II
- **CC6.1**: Logical access controls - DEFICIENT
  - Tenant isolation not enforced at token level
- **CC7.2**: System monitoring - GAPS
  - No alerting for cross-tenant token usage
- **Impact**: Failed audit, loss of customer trust

### PCI-DSS (Payment Card Industry Data Security Standard)
- **Requirement 7**: Restrict access to cardholder data - VIOLATED
  - Tenant A could access Tenant B's payment data
- **Requirement 10**: Track and monitor access - GAPS
  - Token tenant origin not logged
- **Impact**: Loss of PCI compliance, inability to process payments

---

## Remediation Priority

### Immediate Actions (Within 24 Hours)
1. **Add tenant_id to oauth_access_tokens table**
2. **Create custom Token model with BelongsToTenant trait**
3. **Add ValidateClientTenant middleware**
4. **Deploy emergency monitoring for cross-tenant access attempts**

### Short-Term Actions (Within 7 Days)
1. **Add tenant_id to oauth_refresh_tokens table**
2. **Implement token-to-client tenant validation**
3. **Add comprehensive test coverage for cross-tenant attacks**
4. **Deploy security alerting for tenant mismatches**

### Medium-Term Actions (Within 30 Days)
1. **Enhance TenantTokenValidator with multi-level checks**
2. **Add tenant context audit logging**
3. **Implement token revocation on tenant mismatch**
4. **Conduct penetration testing focused on tenant isolation**

---

## Code Change Summary

### Files Requiring Immediate Changes
1. `database/migrations/2016_06_01_000002_create_oauth_access_tokens_table.php`
   - Add tenant_id column

2. `packages/Webkul/Tenant/src/Database/Migrations/2026_02_10_000004_add_tenant_id_to_wave3_tables.php`
   - Add `oauth_access_tokens` to $tables array

3. `packages/Webkul/AdminApi/src/Models/Token.php` (NEW FILE)
   - Create custom Token model with BelongsToTenant

4. `packages/Webkul/AdminApi/src/Providers/AdminApiServiceProvider.php`
   - Register custom Token model

5. `packages/Webkul/Tenant/src/Http/Middleware/ValidateClientTenant.php` (NEW FILE)
   - Create client-tenant validation middleware

6. `packages/Webkul/Tenant/src/Http/Middleware/TenantTokenValidator.php`
   - Enhance with client-tenant verification

7. `packages/Webkul/AdminApi/src/Routes/admin-api.php`
   - Add new middleware to stack

---

## Security Testing Recommendations

### Penetration Testing Scenarios
1. **Token Replay Attack**
   - Capture token from Tenant A
   - Attempt to use it to access Tenant B resources

2. **Client Manipulation Attack**
   - Create client for Tenant A
   - Reassign client to Tenant B in database
   - Attempt to authenticate Tenant B users with Tenant A's client

3. **User Migration Attack**
   - Create user in Tenant A with valid token
   - Change user's tenant_id to Tenant B
   - Verify token becomes invalid

4. **Refresh Token Persistence Attack**
   - Obtain refresh token from Tenant A
   - Delete Tenant A
   - Attempt to use refresh token to get new access token

### Automated Security Scans
```bash
# Static analysis for tenant isolation
./vendor/bin/phpstan analyse --level=8 packages/Webkul/AdminApi
./vendor/bin/phpstan analyse --level=8 packages/Webkul/Tenant

# Dependency vulnerability scan
composer audit

# OWASP ZAP automated scan
zap-cli quick-scan http://unopim.local/api/v1/rest/

# SQL injection testing on tenant_id parameters
sqlmap -u "http://unopim.local/api/v1/rest/products?tenant_id=1" \
       --cookie="Bearer TOKEN" \
       --technique=BEUST
```

---

## Appendix: Attack Surface Summary

| Attack Vector | Likelihood | Impact | Risk Score |
|--------------|------------|--------|------------|
| Cross-tenant token replay | HIGH | CRITICAL | 9.1 |
| Client-tenant mismatch | MEDIUM | HIGH | 7.4 |
| User tenant_id manipulation | MEDIUM | HIGH | 7.2 |
| Refresh token persistence | MEDIUM | HIGH | 6.8 |
| Permission bypass via guard confusion | LOW | MEDIUM | 5.3 |
| Token-client association bypass | LOW | MEDIUM | 4.7 |

**Overall Risk Rating**: 🔴 **CRITICAL**

---

## Conclusion

The UnoPim API authentication layer has **severe tenant isolation vulnerabilities** that pose immediate risk to data confidentiality, integrity, and regulatory compliance. The root cause is the **missing tenant_id column in oauth_access_tokens** and the **lack of multi-level tenant validation** in the token authentication flow.

**Immediate remediation is required** to prevent potential data breaches, regulatory fines, and loss of customer trust.

### Recommended Actions
1. ✅ Implement all CRITICAL fixes within 24 hours
2. ✅ Deploy emergency monitoring for cross-tenant access attempts
3. ✅ Conduct immediate penetration testing
4. ✅ Notify compliance team and legal counsel
5. ✅ Prepare incident response plan for potential breaches

---

**End of Security Audit Report**
