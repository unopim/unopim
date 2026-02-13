# Cache & Session Isolation Architecture Analysis

## Current Architecture (VULNERABLE)

```
┌─────────────────────────────────────────────────────────────────┐
│                         REQUEST FLOW                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tenant A Request          Tenant B Request                      │
│  (subdomain: a.app.com)    (subdomain: b.app.com)               │
│         │                         │                              │
│         ▼                         ▼                              │
│  ┌──────────────┐          ┌──────────────┐                     │
│  │  Middleware  │          │  Middleware  │                     │
│  │  Sets:       │          │  Sets:       │                     │
│  │  tenant=1    │          │  tenant=2    │                     │
│  └──────┬───────┘          └──────┬───────┘                     │
│         │                         │                              │
│         └─────────┬───────────────┘                              │
│                   ▼                                              │
│         ┌─────────────────────┐                                 │
│         │   Application       │                                 │
│         │   Core Singleton    │  ⚠️ SHARED STATE               │
│         │  ┌───────────────┐  │                                 │
│         │  │ $currentChannel│  │  ← Tenant A sets              │
│         │  │ $currentCurrency│ │  ← Tenant B reads stale!      │
│         │  │ $singletonCache│  │  ← No tenant key              │
│         │  └───────────────┘  │                                 │
│         └─────────┬───────────┘                                 │
│                   │                                              │
│         ┌─────────┴───────────┐                                 │
│         │                     │                                 │
│         ▼                     ▼                                 │
│  ┌────────────┐        ┌────────────┐                          │
│  │ Cache Layer│        │  Sessions  │                          │
│  │            │        │            │                          │
│  │ ❌ FPC     │        │ ❌ Shared  │                          │
│  │  No tenant │        │  cookies   │                          │
│  │  context   │        │  Same DB   │                          │
│  │            │        │  table     │                          │
│  │ ⚠️ Repos   │        │            │                          │
│  │  Disabled  │        │ ⚠️ Session │                          │
│  │  by default│        │  key reuse │                          │
│  │            │        │            │                          │
│  │ ✅ TenantCache      │            │                          │
│  │  (unused)  │        │            │                          │
│  └────────────┘        └────────────┘                          │
│         │                     │                                 │
│         ▼                     ▼                                 │
│  ┌──────────────────────────────┐                              │
│  │   Redis/Memcached/File       │                              │
│  │   GLOBAL CACHE NAMESPACE     │                              │
│  │                              │                              │
│  │  hash('url')  → Content A    │  ⚠️ SHARED                  │
│  │  hash('url')  → Content A    │  ← Tenant B gets A's data!  │
│  └──────────────────────────────┘                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow: Full Page Cache Attack

```
Step 1: Tenant A Request
┌────────────────────────────────────────────────┐
│ GET /admin/products                            │
│ Host: tenant-a.app.example.com                 │
│                                                │
│ ┌────────────────┐                             │
│ │ TenantMiddleware│ → Sets core()->tenantId=1  │
│ └────────┬───────┘                             │
│          │                                      │
│          ▼                                      │
│ ┌────────────────┐                             │
│ │ ProductController│ → Renders products list   │
│ └────────┬───────┘                             │
│          │                                      │
│          ▼                                      │
│ ┌────────────────────────────────┐             │
│ │ ResponseCache::cache()         │             │
│ │  Key: hash('/admin/products')  │ ❌ NO TENANT│
│ │  Value: HTML for Tenant A      │             │
│ └────────────────────────────────┘             │
└────────────────────────────────────────────────┘

Step 2: Tenant B Request (ATTACK)
┌────────────────────────────────────────────────┐
│ GET /admin/products                            │
│ Host: tenant-b.app.example.com                 │
│                                                │
│ ┌────────────────┐                             │
│ │ TenantMiddleware│ → Sets core()->tenantId=2  │
│ └────────┬───────┘                             │
│          │                                      │
│          ▼                                      │
│ ┌────────────────────────────────┐             │
│ │ ResponseCache::get()           │             │
│ │  Key: hash('/admin/products')  │ ❌ SAME KEY │
│ │  Returns: HTML for Tenant A    │ 💥 BREACH  │
│ └────────────────────────────────┘             │
│          │                                      │
│          ▼                                      │
│ ┌────────────────┐                             │
│ │ Tenant B sees  │                             │
│ │ Tenant A's     │                             │
│ │ products!      │                             │
│ └────────────────┘                             │
└────────────────────────────────────────────────┘
```

## Secure Architecture (PROPOSED)

```
┌─────────────────────────────────────────────────────────────────┐
│                    TENANT-ISOLATED ARCHITECTURE                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tenant A Request          Tenant B Request                      │
│         │                         │                              │
│         ▼                         ▼                              │
│  ┌──────────────┐          ┌──────────────┐                     │
│  │  Middleware  │          │  Middleware  │                     │
│  │  Sets:       │          │  Sets:       │                     │
│  │  tenant=1    │          │  tenant=2    │                     │
│  │  + Resets    │          │  + Resets    │                     │
│  │  Core state  │          │  Core state  │                     │
│  └──────┬───────┘          └──────┬───────┘                     │
│         │                         │                              │
│         └─────────┬───────────────┘                              │
│                   ▼                                              │
│         ┌─────────────────────┐                                 │
│         │   Application       │                                 │
│         │   Core Singleton    │  ✅ RESET ON TENANT CHANGE     │
│         │  ┌───────────────┐  │                                 │
│         │  │ Tenant-keyed  │  │  ← Cache by tenant              │
│         │  │ singletons    │  │  ← Separate namespaces         │
│         │  │ [t1][t2]      │  │  ← Auto-cleared                │
│         │  └───────────────┘  │                                 │
│         └─────────┬───────────┘                                 │
│                   │                                              │
│         ┌─────────┴───────────┐                                 │
│         │                     │                                 │
│         ▼                     ▼                                 │
│  ┌────────────┐        ┌────────────┐                          │
│  │ Cache Layer│        │  Sessions  │                          │
│  │            │        │            │                          │
│  │ ✅ TenantCache      │ ✅ Tenant  │                          │
│  │  HMAC prefix│        │  cookie    │                          │
│  │  All usage │        │  t1_session│                          │
│  │            │        │  t2_session│                          │
│  │ ✅ FPC     │        │            │                          │
│  │  Tenant in │        │ ✅ DB      │                          │
│  │  hash      │        │  tenant_id │                          │
│  │            │        │  column    │                          │
│  │ ✅ Images  │        │            │                          │
│  │  Tenant    │        │            │                          │
│  │  prefix    │        │            │                          │
│  └────────────┘        └────────────┘                          │
│         │                     │                                 │
│         ▼                     ▼                                 │
│  ┌──────────────────────────────┐                              │
│  │   Redis/Memcached/File       │                              │
│  │   TENANT-NAMESPACED CACHE    │                              │
│  │                              │                              │
│  │  hmac(t1):url → Content A    │  ✅ ISOLATED                │
│  │  hmac(t2):url → Content B    │  ← Separate keys            │
│  │                              │                              │
│  │  Tags: [tenant:1] [tenant:2] │  ← Easy flush               │
│  └──────────────────────────────┘                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Cache Key Comparison

### CURRENT (Vulnerable)

| Component | Cache Key Pattern | Tenant Isolation |
|-----------|-------------------|------------------|
| FPC | `hash(url)` | ❌ None |
| Repository | `repo:method:args` (disabled) | ⚠️ Prefix exists but unused |
| Image Cache | `intervention:template:path` | ❌ None |
| Rate Limiter | `user_id` or `ip` | ❌ None |
| Session | `unopim_session` (cookie) | ❌ Shared |
| Config | `config:key` | ❌ Global |
| View | `views/hash.php` | ❌ Global |

### PROPOSED (Secure)

| Component | Cache Key Pattern | Tenant Isolation |
|-----------|-------------------|------------------|
| FPC | `hmac(tenant_id):hash(url)` | ✅ Full |
| Repository | `hmac(tenant_id):repo:method:args` | ✅ Full |
| Image Cache | `hmac(tenant_id):intervention:path` | ✅ Full |
| Rate Limiter | `tenant_id:user_id` | ✅ Full |
| Session | `unopim_t{id}_session` (cookie) | ✅ Full |
| Config | `hmac(tenant_id):config:key` | ✅ Full |
| View | `views/tenant_{id}/hash.php` | ✅ Full |

## Session Isolation Comparison

### CURRENT (File Driver)

```
storage/framework/sessions/
├── sess_abc123  ← Could be Tenant A or B (no isolation)
├── sess_def456
└── sess_ghi789

Cookie: unopim_session=abc123
       ↓
Any tenant can use this session ID
```

### PROPOSED (File Driver)

```
storage/framework/sessions/
├── tenant_1/
│   ├── sess_abc123  ← Only Tenant A
│   └── sess_def456
├── tenant_2/
│   ├── sess_ghi789  ← Only Tenant B
│   └── sess_jkl012
└── platform/
    └── sess_mno345  ← Platform operators

Cookie: unopim_t1_session=abc123  (Tenant A)
Cookie: unopim_t2_session=ghi789  (Tenant B)
       ↓
Separate cookie namespaces per tenant
```

### PROPOSED (Database Driver)

```sql
-- CURRENT (Vulnerable)
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    payload TEXT,
    last_activity INT
);
-- ❌ No tenant_id → Session collision possible

-- PROPOSED (Secure)
CREATE TABLE sessions (
    id VARCHAR(255),
    tenant_id INT NULL,  -- ✅ Tenant isolation
    user_id BIGINT,
    payload TEXT,
    last_activity INT,
    PRIMARY KEY (id, tenant_id),  -- Composite key
    INDEX idx_tenant_activity (tenant_id, last_activity)
);

-- Session queries always scoped:
SELECT * FROM sessions
WHERE id = ? AND tenant_id = ?;
```

## Rate Limiter Key Comparison

### CURRENT

```
Request from Tenant A, User 1:
  Rate limit key: "user_1"
  Limit: 60/minute

Request from Tenant B, User 1:  ❌ SAME USER ID
  Rate limit key: "user_1"       ← Shares limit with Tenant A!
  Limit: 60/minute (shared counter)
```

### PROPOSED

```
Request from Tenant A, User 1:
  Rate limit key: "tenant_1:user_1"
  Limit: 60/minute

Request from Tenant B, User 1:
  Rate limit key: "tenant_2:user_1"  ✅ Isolated
  Limit: 60/minute (separate counter)
```

## Core Singleton State Management

### CURRENT (Shared State in Queue Workers)

```
Queue Worker (Long-Running Process)
┌─────────────────────────────────────┐
│ Job 1 (Tenant A)                    │
│  core()->setCurrentTenantId(1)      │
│  $channel = core()->getCurrentChannel() │
│  → Sets: $this->currentChannel = 'tenant-a-default' │
│                                     │
├─────────────────────────────────────┤
│ Job 2 (Tenant B) - IMMEDIATELY AFTER│
│  core()->setCurrentTenantId(2)      │
│  $channel = core()->getCurrentChannel() │
│  → Returns: 'tenant-a-default'  ❌ │  ← WRONG TENANT!
│                                     │
└─────────────────────────────────────┘

Problem: Instance properties persist across jobs
```

### PROPOSED (Reset on Tenant Change)

```
Queue Worker (Long-Running Process)
┌─────────────────────────────────────┐
│ Job 1 (Tenant A)                    │
│  core()->setCurrentTenantId(1)      │
│   → Triggers: resetTenantState()    │
│  $channel = core()->getCurrentChannel() │
│  → Fresh DB query                   │
│                                     │
├─────────────────────────────────────┤
│ Job 2 (Tenant B) - IMMEDIATELY AFTER│
│  core()->setCurrentTenantId(2)      │
│   → Triggers: resetTenantState() ✅│
│  $channel = core()->getCurrentChannel() │
│  → Fresh DB query for Tenant B     │
│                                     │
└─────────────────────────────────────┘

Solution: Clear cached state on tenant switch
```

## Implementation Checklist

### Phase 1: CRITICAL (Week 1)
- [ ] Disable FPC in production (`RESPONSE_CACHE_ENABLED=false`)
- [ ] Add `resetTenantState()` to Core singleton
- [ ] Call `resetTenantState()` in `setCurrentTenantId()`
- [ ] Add tenant ID to session cookie name
- [ ] Block `php artisan config:cache` in multi-tenant mode
- [ ] Add tests for cache isolation

### Phase 2: HIGH (Week 2-3)
- [ ] Replace all `Cache::` usage with `TenantCache::`
- [ ] Add tenant context to FPC hasher
- [ ] Update rate limiter keys
- [ ] Add tenant prefix to image cache
- [ ] Add tenant_id column to sessions table
- [ ] Update session queries with tenant scoping

### Phase 3: MEDIUM (Week 4-5)
- [ ] Implement tenant-aware view caching
- [ ] Fix FPC listeners to use TenantCache
- [ ] Add tenant tags to cache operations
- [ ] Update queue jobs with tenant context
- [ ] Add pre-commit hooks for unsafe Cache usage

### Phase 4: Testing (Week 6)
- [ ] Comprehensive tenant isolation test suite
- [ ] Load testing with mixed tenant requests
- [ ] Security penetration testing
- [ ] Performance benchmarking
- [ ] Documentation updates

---

## Key Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Cache isolation coverage | 15% | 100% |
| Session isolation | 0% | 100% |
| Core state management | 20% | 100% |
| Test coverage (isolation) | 5% | 95% |
| CVSS Score | 9.1 (CRITICAL) | 0 (None) |

---

**Generated**: 2026-02-13
**Document Version**: 1.0
