# Manual UI Testing Guide - Multi-Tenant Channel Connector

**Version**: 1.0
**Target Audience**: Business Users, QA Team, Product Managers
**Prerequisites**: Access to UnoPim admin panel with multiple tenant environments
**Estimated Time**: 45-60 minutes

---

## Test Environment Setup

### Prerequisites
1. **Access Requirements**:
   - Admin access to UnoPim instance
   - At least 2 test tenants configured (e.g., "Tenant A" and "Tenant B")
   - API client credentials for each tenant

2. **Test Data Preparation**:
   - Create at least 2 products in Tenant A
   - Create at least 2 different products in Tenant B
   - Set up a Shopify connection for each tenant (use test stores)

---

## Test Scenarios

## SCENARIO 1: Tenant Data Isolation (Products)

### Objective
Verify that users from Tenant A cannot view, edit, or access products belonging to Tenant B.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as admin for **Tenant A** | Dashboard loads showing Tenant A context |
| 2 | Navigate to **Catalog > Products** | Only Tenant A products are visible |
| 3 | Note the product IDs visible (e.g., PROD-A-001, PROD-A-002) | Product list displays correctly |
| 4 | Log out from Tenant A | Session ends |
| 5 | Login as admin for **Tenant B** | Dashboard loads showing Tenant B context |
| 6 | Navigate to **Catalog > Products** | Only Tenant B products are visible |
| 7 | Search for Tenant A product ID (e.g., PROD-A-001) | No results found - "No products found" message |
| 8 | Try to access Tenant A product directly via URL: `/admin/catalog/products/edit/{PROD-A-001}` | **403 Forbidden** or **404 Not Found** error |
| 9 | Verify Tenant B products (e.g., PROD-B-001, PROD-B-002) are visible | Product list displays Tenant B products only |

**Pass Criteria**: Tenant B user cannot see or access any Tenant A products.

---

## SCENARIO 2: Channel Connector Isolation

### Objective
Verify that channel connectors are isolated per tenant and cannot be accessed across tenants.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as admin for **Tenant A** | Dashboard loads for Tenant A |
| 2 | Navigate to **Integrations > Channel Connectors** | List shows only Tenant A's connectors |
| 3 | Create a new Shopify connector: <br>- Name: "Tenant A Shopify" <br>- Store: `tenant-a.myshopify.com` | Connector created successfully |
| 4 | Note the connector code (e.g., `tenant-a-shopify`) | Connector appears in list |
| 5 | Copy the connector ID/URL | ID copied to clipboard |
| 6 | Open incognito/private browser window | New session starts |
| 7 | Login as admin for **Tenant B** | Dashboard loads for Tenant B |
| 8 | Navigate to **Integrations > Channel Connectors** | List shows only Tenant B's connectors (or empty) |
| 9 | Try to access Tenant A's connector directly via URL | **403 Forbidden** or **404 Not Found** |
| 10 | Create a Shopify connector for Tenant B: <br>- Name: "Tenant B Shopify" <br>- Store: `tenant-b.myshopify.com` | Connector created successfully |
| 11 | Switch back to Tenant A session | Session restored |
| 12 | Navigate to **Integrations > Channel Connectors** | Only Tenant A's connector visible |
| 13 | Verify Tenant B's connector is NOT visible | Tenant B connector not shown |

**Pass Criteria**: Each tenant sees only their own channel connectors.

---

## SCENARIO 3: API Cross-Tenant Access Prevention

### Objective
Verify that API tokens issued for Tenant A cannot access Tenant B's data via API.

### Prerequisites
- API client credentials for both tenants
- Tool like Postman, cURL, or API client extension

### Steps

#### Part A: Get Tenant A Token

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Make API request to obtain Tenant A token:<br>```POST /oauth/token```<br>```json{<br>  "grant_type": "password",<br>  "client_id": "TENANT_A_CLIENT_ID",<br>  "client_secret": "TENANT_A_SECRET",<br>  "username": "tenant-a@example.com",<br>  "password": "password"<br>}``` | Returns access_token for Tenant A |
| 2 | Copy the access_token | Token ready for use |

#### Part B: Test Valid Access (Tenant A Data)

| Step | Action | Expected Result |
|------|--------|-----------------|
| 3 | Using Tenant A token, get products:<br>```GET /api/v1/rest/products```<br>Header: `Authorization: Bearer {TENANT_A_TOKEN}` | Returns **only** Tenant A products |
| 4 | Note the product IDs in response | Product IDs belong to Tenant A |

#### Part C: Test Cross-Tenant Access (Should Fail)

| Step | Action | Expected Result |
|------|--------|-----------------|
| 5 | Using same Tenant A token, try to access Tenant B product:<br>```GET /api/v1/rest/products/{TENANT_B_PRODUCT_ID}```<br>Header: `Authorization: Bearer {TENANT_A_TOKEN}` | **404 Not Found** or **403 Forbidden** |
| 6 | Using same Tenant A token, try to create product in Tenant B context:<br>```POST /api/v1/rest/products```<br>Header: `Authorization: Bearer {TENANT_A_TOKEN}`<br>Body: Product with tenant context | Product created in **Tenant A** (ignores any tenant_id in request) |

**Pass Criteria**: API token from Tenant A cannot access or modify Tenant B resources.

---

## SCENARIO 4: Sync Job Tenant Isolation

### Objective
Verify that sync jobs are isolated and run within their tenant context.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as admin for **Tenant A** | Dashboard loads |
| 2 | Navigate to **Integrations > Channel Connectors** | Tenant A connectors shown |
| 3 | Click on your connector and select **Sync** > **Trigger Full Sync** | Sync job created and starts running |
| 4 | Note the job ID from the URL or confirmation message | Job ID noted (e.g., `sync-job-001`) |
| 5 | Navigate to **Integrations > Sync Jobs** | Sync job list shown |
| 6 | Verify the job is listed with status "Running" or "Pending" | Job visible for Tenant A |
| 7 | Log out and login as **Tenant B** admin | Dashboard loads for Tenant B |
| 8 | Navigate to **Integrations > Sync Jobs** | Sync job list shown for Tenant B |
| 9 | Verify **Tenant A's sync job is NOT visible** | Only Tenant B jobs visible (or empty) |
| 10 | Try to access Tenant A's job directly: `/admin/channel/sync/{sync-job-001}` | **403 Forbidden** or **404 Not Found** |

**Pass Criteria**: Each tenant sees only their own sync jobs.

---

## SCENARIO 5: Webhook Tenant Isolation

### Objective
Verify that incoming webhooks are properly isolated and update only the correct tenant's products.

### Prerequisites
- Webhook endpoint configured for each tenant
- Tool to send webhook requests (e.g., Postman, cURL)

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as **Tenant A** admin | Dashboard loads |
| 2 | Note a specific product's data (e.g., product name "Original Name A") | Product data noted |
| 3 | Get Tenant A's webhook token from connector settings | Webhook token copied |
| 4 | Send webhook to Tenant A with update:<br>```POST /channel/webhooks/{TENANT_A_WEBHOOK_TOKEN}```<br>```json{<br>  "event": "product.updated",<br>  "external_id": "SHOPIFY_ID_123",<br>  "data": {<br>    "title": "Updated Name A",<br>    "sku": "SKU-A"<br>  }<br>}``` | Webhook accepted (200 OK) |
| 5 | Navigate to the product and verify name changed to "Updated Name A" | Product updated successfully |
| 6 | Login as **Tenant B** admin | Dashboard loads |
| 7 | Find product with SKU "SKU-B" (different from Tenant A) | Product found |
| 8 | Verify the product name was NOT changed by Tenant A's webhook | Product name unchanged |

**Pass Criteria**: Webhooks only update products within their tenant scope.

---

## SCENARIO 6: Permission Boundary Test

### Objective
Verify that tenant permissions are isolated and platform permissions work correctly.

### Steps

### Part A: Tenant User Permissions

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as **Tenant A** user with limited permissions (not platform admin) | Dashboard loads |
| 2 | Try to access platform-level settings: `/admin/settings` | **403 Forbidden** or settings page with limited options |
| 3 | Navigate to **Catalog > Products** | Products visible |
| 4 | Create a new product | Product created in Tenant A |
| 5 | Note the product ID | Product ID noted |

### Part B: Platform Admin Cross-Tenant Access

| Step | Action | Expected Result |
|------|--------|-----------------|
| 6 | Log out and login as **Platform Admin** (user with `tenant_id = NULL`) | Platform dashboard loads |
| 7 | Navigate to **System > Tenants** | List of all tenants visible |
| 8 | Select **Tenant A** from the list | Tenant A context activated |
| 9 | Navigate to **Catalog > Products** | Tenant A products visible |
| 10 | Select **Tenant B** from tenant switcher | Tenant B context activated |
| 11 | Navigate to **Catalog > Products** | Tenant B products visible |
| 12 | Create product while in Tenant B context | Product created in Tenant B (not Platform) |

**Pass Criteria**: Tenant users cannot access platform features; Platform admin can switch between tenant contexts.

---

## SCENARIO 7: User Management & Isolation

### Objective
Verify that users are properly scoped to their tenants.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as **Tenant A** admin | Dashboard loads |
| 2 | Navigate to **Settings > Users** | List shows only Tenant A users |
| 3 | Click **Add User** and create new user | User creation form opens |
| 4 | Fill user details and save | User created under Tenant A |
| 5 | Log out and login as **Tenant B** admin | Dashboard loads |
| 6 | Navigate to **Settings > Users** | List shows only Tenant B users |
| 7 | Verify Tenant A users are NOT visible | Only Tenant B users shown |
| 8 | Try to access Tenant A user directly via URL | **403 Forbidden** or **404 Not Found** |

**Pass Criteria**: User lists are isolated per tenant.

---

## SCENARIO 8: API Credentials Security

### Objective
Verify that API credentials (tokens, secrets) are never exposed and are tenant-scoped.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as **Tenant A** admin | Dashboard loads |
| 2 | Navigate to **Integrations > API Keys** | List of API keys for Tenant A |
| 3 | Create a new API key | API key created with masked secret |
| 4 | Note: The secret should be partially masked (e.g., `shpat_...xxx`) | Secret is masked for security |
| 5 | Make API request to list connectors:<br>```GET /api/v1/rest/channel-connectors```<br>Header: `Authorization: Bearer {TENANT_A_TOKEN}` | Returns list of connectors |
| 6 | **CRITICAL**: Verify response does NOT contain:<br>- `access_token` field<br>- `secret` field in plain text<br>- `password` field | No sensitive credentials exposed |
| 7 | Verify `credentials` field is empty or contains only public info | No secrets in response |

**Pass Criteria**: API responses never contain sensitive credentials.

---

## SCENARIO 9: Tenant Deletion & Orphan Handling

### Objective
Verify that when a tenant is deleted, their data is handled correctly.

### ⚠️ WARNING: This scenario may involve data deletion. Use test environment only!

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as **Platform Admin** | Platform dashboard loads |
| 2 | Navigate to **System > Tenants** | Tenant list shown |
| 3 | Identify a test tenant to delete (e.g., "Test Tenant X") | Tenant noted |
| 4 | Verify the tenant has:<br>- Products<br>- Channel connectors<br>- API tokens<br>- Users | Data verified |
| 5 | Click **Delete** on the tenant | Confirmation dialog appears |
| 6 | Confirm deletion with warning acknowledgment | Tenant deleted |
| 7 | Wait for deletion to complete | Success message shown |
| 8 | Try to login as a user from deleted tenant | **Login rejected** - user not found |
| 9 | Check database (via admin or direct query) - verify products are deleted or marked deleted | Cascade delete worked |
| 10 | Check if any orphaned records exist (database check) | No orphaned records |

**Pass Criteria**: Tenant deletion cascades to all related data.

---

## SCENARIO 10: Concurrent Multi-Tenant Session Test

### Objective
Verify that logging into multiple tenants in different browser sessions maintains proper isolation.

### Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Open **Chrome/Edge** and login as **Tenant A** | Tenant A session active |
| 2 | Navigate to **Catalog > Products** and note products | Tenant A products visible |
| 3 | Open **Firefox** (different browser) and login as **Tenant B** | Tenant B session active |
| 4 | Navigate to **Catalog > Products** | Tenant B products visible |
| 5 | In Tenant A browser, create a new product "Product A-Test" | Product created |
| 6 | In Tenant B browser, refresh product list | "Product A-Test" is NOT visible |
| 7 | In Tenant B browser, create product "Product B-Test" | Product created |
| 8 | In Tenant A browser, refresh product list | "Product B-Test" is NOT visible |
| 9 | Verify each browser shows only its tenant's data | Sessions properly isolated |

**Pass Criteria**: Multiple concurrent sessions maintain tenant isolation.

---

## Test Results Checklist

Use this checklist to track your testing progress:

| Scenario | Tested By | Date | Result | Notes |
|----------|-----------|------|--------|-------|
| 1. Product Data Isolation | | | Pass / Fail | |
| 2. Channel Connector Isolation | | | Pass / Fail | |
| 3. API Cross-Tenant Prevention | | | Pass / Fail | |
| 4. Sync Job Isolation | | | Pass / Fail | |
| 5. Webhook Isolation | | | Pass / Fail | |
| 6. Permission Boundaries | | | Pass / Fail | |
| 7. User Management Isolation | | | Pass / Fail | |
| 8. API Credentials Security | | | Pass / Fail | |
| 9. Tenant Deletion Handling | | | Pass / Fail | |
| 10. Concurrent Session Isolation | | | Pass / Fail | |

---

## Common Issues & Troubleshooting

### Issue: "403 Forbidden" when accessing own data
**Possible Cause**: Tenant context not set correctly
**Solution**: Log out and log back in; clear browser cache

### Issue: Can see other tenant's products
**Possible Cause**: Query missing tenant scope
**Solution**: Report to development team - this is a bug

### Issue: API returns wrong tenant's data
**Possible Cause**: Token issued without tenant context
**Solution**: Regenerate API token after ensuring OAuth client has tenant_id

### Issue: Webhook not updating product
**Possible Cause**: Webhook token invalid or product not mapped
**Solution**: Verify webhook token and product-channel mapping exists

---

## Reporting Bugs

If any test fails, document:

1. **Scenario Number**: Which test scenario failed
2. **Steps Taken**: Exact steps to reproduce
3. **Expected Result**: What should have happened
4. **Actual Result**: What actually happened
5. **Screenshots**: Include screenshots if applicable
6. **Browser/Environment**: Browser version, tenant IDs

**Bug Report Template**:
```
Scenario: [X]
Tenant IDs: A=[tenant-a-id], B=[tenant-b-id]
User: [email]
Steps: [brief description]
Error: [error message or screenshot]
```

---

## Sign-Off

**Tester Name**: ___________________
**Test Date**: ___________________
**Environment**: [Development / Staging / Production]
**Overall Result**: [All Pass / Fail - See Notes]

**Notes**:
_____________________________________________________________________
_____________________________________________________________________
_____________________________________________________________________
