# Production Readiness Checklist - ChannelConnector Multi-Tenant Architecture

**Version**: 1.0
**Last Updated**: 2026-02-15
**Scope**: ChannelConnector package tenant isolation and security
**Status**: DRAFT - Items marked with [REQUIRED] must be completed before production deployment

---

## 1. Database Schema & Migrations

### 1.1 Migration Verification
- [ ] All tenant migration waves (1-7 + shopify) applied successfully
- [ ] `oauth_access_tokens` has `tenant_id` column with foreign key
- [ ] `oauth_refresh_tokens` has `tenant_id` column with foreign key
- [ ] All ChannelConnector tables have `tenant_id` column
- [ ] Composite indexes created on `(tenant_id, id)` for all tenant tables
- [ ] Unique constraints include `tenant_id` where applicable (e.g., `(tenant_id, code)`)

### 1.2 Data Integrity
- [ ] Cascade delete rules configured correctly
- [ ] Foreign key constraints enabled in SQLite (testing) and MySQL/PostgreSQL (production)
- [ ] Tenant orphan detection script run - no orphaned records
- [ ] Default tenant (id=1) exists and is active

### 1.3 Migration Rollback Plan
- [ ] Down migrations tested in development environment
- [ ] Rollback procedure documented
- [ ] Database backup strategy in place before production migration

---

## 2. OAuth Token Security (CRITICAL)

### 2.1 Custom Token Models
- [ ] `Webkul\AdminApi\Models\Token` created with `BelongsToTenant` trait
- [ ] `Webkul\AdminApi\Models\RefreshToken` created with `BelongsToTenant` trait
- [ ] Custom models registered in `AdminApiServiceProvider`
- [ ] Token creation sets `tenant_id` from current context
- [ ] Token validation includes tenant verification

### 2.2 Token Issuance
- [ ] Password grant tokens capture `tenant_id`
- [ ] Client credentials grant tokens capture `tenant_id`
- [ ] Personal access tokens capture `tenant_id`
- [ ] Refresh tokens inherit `tenant_id` from access tokens

### 2.3 Token Validation
- [ ] `TenantTokenValidator` middleware validates token-tenant association
- [ ] Token-to-client tenant validation implemented
- [ ] Cross-tenant token requests rejected with 403
- [ ] Orphaned token detection (tenant deleted) functional

### 2.4 Token Revocation
- [ ] Token revocation propagates to refresh tokens
- [ ] Tenant deletion revokes all associated tokens
- [ ] Token cleanup job scheduled

---

## 3. API Authentication & Authorization

### 3.1 Middleware Chain
- [ ] `auth:api` - Passport token validation
- [ ] `tenant.token` - TenantTokenValidator
- [ ] `tenant.safe-errors` - Error sanitization
- [ ] `api.scope` - Permission validation via TenantPermissionGuard
- [ ] `accept.json` - Content negotiation
- [ ] `request.locale` - Locale setup

### 3.2 Permission Guard
- [ ] `TenantPermissionGuard` blocks platform permissions for tenant users
- [ ] Platform users can access all permissions
- [ ] Tenant users restricted to tenant-scoped permissions
- [ ] Custom permission types work correctly

### 3.3 API Response Security
- [ ] Credentials never exposed in API responses
- [ ] Tenant IDs not leaked in error messages
- [ ] Stack traces sanitized in production

---

## 4. Queue Jobs & Background Processing

### 4.1 Tenant-Aware Jobs
- [ ] `ProcessSyncJob` uses `TenantAwareJob` trait
- [ ] `ProcessWebhookJob` uses `TenantAwareJob` trait
- [ ] Job dispatch captures current tenant context
- [ ] Job execution restores tenant context
- [ ] Failed jobs preserve tenant context

### 4.2 Queue Configuration
- [ ] Queue worker configured with tenant context middleware
- [ ] Horizon/Supervisor configuration includes tenant setup
- [ ] Queue connection tested with multiple tenants

---

## 5. Webhook Security

### 5.1 Webhook Reception
- [ ] Webhook tokens validated before processing
- [ ] HMAC signature verification functional
- [ ] Webhook endpoint includes tenant context

### 5.2 Webhook Processing
- [ ] Webhook jobs queued with tenant context
- [ ] Product updates respect tenant boundaries
- [ ] Cross-tenant webhook attacks prevented

---

## 6. Testing Coverage

### 6.1 Unit Tests
- [ ] Model relationships tested (with tenant scoping)
- [ ] `BelongsToTenant` trait functionality tested
- [ ] TenantScope global scope tested

### 6.2 Integration Tests
- [ ] End-to-end sync tests pass (all 80 tests)
- [ ] API authentication with tenant context tested
- [ ] Webhook processing with tenant isolation tested
- [ ] ACL enforcement tests pass

### 6.3 Security Tests
- [ ] Cross-tenant access attempt tests
- [ ] Token replay attack tests
- [ ] Client-tenant mismatch tests
- [ ] Orphaned tenant handling tests

### 6.4 Performance Tests
- [ ] Query performance with tenant indexes verified
- [ ] Multi-tenant concurrent access tested
- [ ] Database query count acceptable

---

## 7. Monitoring & Logging

### 7.1 Security Event Logging
- [ ] Cross-tenant access attempts logged
- [ ] Token-tenant mismatches logged with alerting
- [ ] Client-tenant mismatches logged
- [ ] Orphaned record detection logged

### 7.2 Performance Monitoring
- [ ] Query execution time monitored
- [ ] Slow query alerts configured
- [ ] Tenant-specific metrics available

### 7.3 Audit Trail
- [ ] Token issuance logged with tenant context
- [ ] Tenant context changes auditable
- [ ] Admin actions attributable to tenant

---

## 8. Configuration

### 8.1 Environment Variables
- [ ] `DB_CONNECTION` set correctly
- [ ] Passport keys generated and secured
- [ ] API token TTLs configured appropriately
- [ ] Queue connection configured

### 8.2 Feature Flags
- [ ] Multi-tenancy enabled
- [ ] Tenant middleware active
- [ ] Tenant-aware models registered

---

## 9. Documentation

### 9.1 Architecture Documentation
- [ ] Tenant testing pattern documented
- [ ] Tenant middleware flow documented
- [ ] Security model documented

### 9.2 Operations Documentation
- [ ] Tenant provisioning process documented
- [ ] Tenant deactivation process documented
- [ ] Incident response procedures documented

---

## 10. Deployment

### 10.1 Pre-Deployment
- [ ] Database backups created
- [ ] Migration dry-run completed
- [ ] Rollback plan tested
- [ ] Stakeholders notified of maintenance window

### 10.2 Deployment
- [ ] Migrations applied in correct order
- [ ] Configuration updated
- [ ] Services restarted
- [ ] Smoke tests passed

### 10.3 Post-Deployment
- [ ] Monitoring dashboards active
- [ ] Error rates within acceptable limits
- [ ] Performance baseline established
- [ ] Security scan completed

---

## 11. Compliance & Legal

### 11.1 Data Privacy
- [ ] GDPR compliance review completed
- [ ] Data residency requirements met
- [ ] Right to erasure functional per-tenant

### 11.2 Security Standards
- [ ] OWASP Top 10 vulnerabilities addressed
- [ ] Penetration testing completed
- [ ] Security audit reviewed

---

## 12. Platform-Tenant Specific

### 12.1 Platform User Management
- [ ] Platform users can switch tenant context
- [ ] Platform users have appropriate permissions
- [ ] Platform audit trail separate from tenant data

### 12.2 Tenant Isolation
- [ ] Tenant A cannot access Tenant B's data
- [ ] Tenant A cannot affect Tenant B's operations
- [ ] Resource isolation verified (products, channels, etc.)

---

## Sign-Off

**Developer**: _________________ Date: ________

**QA Lead**: _________________ Date: ________

**Security Lead**: _________________ Date: ________

**DevOps Lead**: _________________ Date: ________

**Product Owner**: _________________ Date: ________

---

## Notes

### Critical Items Requiring Immediate Attention:
1. Custom Token/RefreshToken models must be deployed before any tenant-specific tokens are issued
2. Migration to add `tenant_id` to OAuth tables must be applied during maintenance window
3. All existing tokens must be backfilled with appropriate `tenant_id` values

### Known Limitations:
1. Tokens issued before tenant_id migration will have null tenant_id
2. Cross-tenant token usage detection relies on middleware chain integrity
3. Refresh token expiry doesn't automatically revoke associated access tokens

### Post-Deployment Monitoring Priorities:
1. Cross-tenant access attempt alerts
2. Token validation error rates
3. Database query performance with tenant indexes
4. Queue job processing times
