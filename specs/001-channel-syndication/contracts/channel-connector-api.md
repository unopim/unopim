# API Contracts: Channel Connector REST API

**Feature**: 001-channel-syndication
**Date**: 2026-02-14
**Base URL**: `/v1/rest/`
**Auth**: OAuth2 Bearer Token (existing Passport auth)
**Middleware**: `auth:api`, `api.scope`, `accept.json`, `request.locale`

---

## Channel Connectors

### List Connectors

```
GET /v1/rest/channel-connectors
```

**ACL**: `channel_connector.connectors.view`

**Query Parameters**:

| Param | Type | Description |
|-------|------|-------------|
| page | int | Page number (default: 1) |
| limit | int | Items per page (default: 10, max: 100) |
| channel_type | string | Filter by type: shopify, salla, easy_orders |
| status | string | Filter by status: connected, disconnected, error |

**Response 200**:
```json
{
  "data": [
    {
      "code": "my-shopify-store",
      "name": "My Shopify Store",
      "channel_type": "shopify",
      "status": "connected",
      "last_synced_at": "2026-02-14T10:30:00Z",
      "settings": {
        "locale_mapping": {"en_US": "en", "ar_AE": "ar"},
        "default_sync_type": "incremental"
      },
      "supported_locales": ["en", "ar"],
      "locale_sync_status": {
        "en_US": "synced",
        "ar_AE": "synced"
      }
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "total": 1
}
```

### Get Connector

```
GET /v1/rest/channel-connectors/{code}
```

**ACL**: `channel_connector.connectors.view`

**Response 200**: Single connector object (same shape as list item).
Credentials are NEVER returned in API responses.

### Create Connector

```
POST /v1/rest/channel-connectors
```

**ACL**: `channel_connector.connectors.create`

**Request Body**:
```json
{
  "code": "my-shopify-store",
  "name": "My Shopify Store",
  "channel_type": "shopify",
  "credentials": {
    "shop_url": "my-store.myshopify.com",
    "access_token": "shpat_xxxx"
  },
  "settings": {
    "locale_mapping": {"en_US": "en"},
    "tax_config": {},
    "conflict_strategy": "pim_wins"
  }
}
```

**Response 201**:
```json
{
  "code": "my-shopify-store",
  "name": "My Shopify Store",
  "channel_type": "shopify",
  "status": "connected",
  "test_result": {
    "success": true,
    "message": "Connection verified successfully"
  }
}
```

**Response 422** (validation error):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The code has already been taken."],
    "credentials.shop_url": ["Invalid Shopify store URL."]
  }
}
```

### Update Connector

```
PUT /v1/rest/channel-connectors/{code}
```

**ACL**: `channel_connector.connectors.edit`

**Request Body**: Same as Create (partial update supported).

### Delete Connector

```
DELETE /v1/rest/channel-connectors/{code}
```

**ACL**: `channel_connector.connectors.delete`

**Response 200**:
```json
{
  "message": "Channel connector deleted successfully."
}
```

### Test Connection

```
POST /v1/rest/channel-connectors/{code}/test
```

**ACL**: `channel_connector.connectors.edit`

**Response 200**:
```json
{
  "success": true,
  "message": "Connection verified",
  "channel_info": {
    "store_name": "My Store",
    "supported_locales": ["en", "ar"],
    "product_count": 1500
  }
}
```

---

## Field Mappings

### List Mappings

```
GET /v1/rest/channel-connectors/{code}/mappings
```

**ACL**: `channel_connector.mappings.view`

**Response 200**:
```json
{
  "data": [
    {
      "id": 1,
      "unopim_attribute_code": "name",
      "channel_field": "title",
      "direction": "export",
      "transformation": null,
      "locale_mapping": {"en_US": "en"}
    }
  ]
}
```

### Save Mappings (Bulk)

```
PUT /v1/rest/channel-connectors/{code}/mappings
```

**ACL**: `channel_connector.mappings.edit`

**Request Body**:
```json
{
  "mappings": [
    {
      "unopim_attribute_code": "name",
      "channel_field": "title",
      "direction": "export",
      "locale_mapping": {"en_US": "en", "ar_AE": "ar"}
    },
    {
      "unopim_attribute_code": "price",
      "channel_field": "price",
      "direction": "export",
      "transformation": {"apply_tax": true, "tax_rate": 0.15}
    }
  ]
}
```

---

## Sync Jobs

### Trigger Sync

```
POST /v1/rest/channel-connectors/{code}/sync
```

**ACL**: `channel_connector.sync.create`

**Request Body**:
```json
{
  "sync_type": "incremental",
  "product_codes": [],
  "force_resync": false,
  "priority": "normal",
  "locales": []
}
```

**Note**: The optional `locales` array allows syncing only
specific locale pairs (e.g., `["en_US", "ar_AE"]`). When
empty or omitted, all configured locale mappings are synced.

**Response 202**:
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "sync_type": "incremental",
  "estimated_products": 150,
  "queue_position": 3
}
```

### Get Sync Job Status

```
GET /v1/rest/channel-connectors/{code}/sync/{job_id}
```

**ACL**: `channel_connector.sync.view`

**Response 200**:
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "running",
  "sync_type": "incremental",
  "total_products": 150,
  "synced_products": 87,
  "failed_products": 3,
  "started_at": "2026-02-14T10:30:00Z",
  "completed_at": null,
  "error_summary": [
    {
      "product_sku": "PROD-123",
      "error_code": "CHN-010",
      "message": "Missing required field: title"
    }
  ]
}
```

### List Sync Jobs

```
GET /v1/rest/channel-connectors/{code}/sync
```

**ACL**: `channel_connector.sync.view`

**Query Parameters**:

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter: pending, running, completed, failed, retrying |
| sync_type | string | Filter: full, incremental, single |
| from | date | Jobs created after this date |
| to | date | Jobs created before this date |

### Retry Failed Sync

```
POST /v1/rest/channel-connectors/{code}/sync/{job_id}/retry
```

**ACL**: `channel_connector.sync.create`

**Response 202**:
```json
{
  "job_id": "new-retry-job-uuid",
  "status": "pending",
  "retry_of": "550e8400-e29b-41d4-a716-446655440000",
  "products_to_retry": 3
}
```

---

## Sync Conflicts

### List Conflicts

```
GET /v1/rest/channel-connectors/{code}/conflicts
```

**ACL**: `channel_connector.conflicts.view`

**Query Parameters**:

| Param | Type | Description |
|-------|------|-------------|
| resolution_status | string | Filter: pending, pim_wins, channel_wins, merged, dismissed |
| product_id | int | Filter by product |

### Resolve Conflict

```
PUT /v1/rest/channel-connectors/{code}/conflicts/{id}/resolve
```

**ACL**: `channel_connector.conflicts.edit`

**Request Body**:
```json
{
  "resolution": "pim_wins",
  "field_overrides": {
    "title": "channel_wins",
    "price": "pim_wins"
  }
}
```

**Response 200**:
```json
{
  "id": 42,
  "resolution_status": "merged",
  "resolved_at": "2026-02-14T11:00:00Z"
}
```

### Get Conflict Detail (with per-locale diff)

```
GET /v1/rest/channel-connectors/{code}/conflicts/{id}
```

**ACL**: `channel_connector.conflicts.view`

**Response 200**:
```json
{
  "id": 42,
  "product_id": 100,
  "conflict_type": "field_mismatch",
  "resolution_status": "pending",
  "conflicting_fields": [
    {
      "field": "title",
      "pim_value": {
        "en_US": "Product Name",
        "ar_AE": "اسم المنتج"
      },
      "channel_value": {
        "en": "Updated Product Name",
        "ar": "اسم المنتج المحدث"
      },
      "is_locale_specific": true
    },
    {
      "field": "price",
      "pim_value": 99.99,
      "channel_value": 89.99,
      "is_locale_specific": false
    }
  ],
  "pim_modified_at": "2026-02-14T09:00:00Z",
  "channel_modified_at": "2026-02-14T10:00:00Z"
}
```

**Note**: For locale-specific fields, `pim_value` and
`channel_value` are locale-keyed objects showing per-locale
diffs. For non-locale fields, values are scalar.

---

## Error Codes

| Code | Description |
|------|-------------|
| CHN-001 | Invalid channel type |
| CHN-002 | Invalid credentials format |
| CHN-003 | Connection test failed |
| CHN-004 | OAuth2 authorization failed |
| CHN-005 | OAuth2 token refresh failed |
| CHN-010 | Missing required channel field |
| CHN-011 | Field type mismatch |
| CHN-012 | Locale not supported by channel |
| CHN-013 | Currency not supported by channel |
| CHN-020 | Channel API rate limit exceeded |
| CHN-021 | Channel API temporarily unavailable |
| CHN-022 | Channel API returned error |
| CHN-023 | Channel API timeout |
| CHN-030 | Product not found in channel |
| CHN-031 | Product create failed in channel |
| CHN-032 | Product update failed in channel |
| CHN-033 | Product delete failed in channel |
| CHN-040 | Sync conflict detected |
| CHN-041 | Conflict resolution failed |
| CHN-050 | Webhook signature invalid |
| CHN-051 | Webhook payload parse error |
| CHN-052 | Webhook event type not supported |
| CHN-060 | Field mapping validation failed |
| CHN-061 | Broken field mapping (attribute deleted) |
| CHN-070 | Tax calculation error |
| CHN-071 | Commission tracking error |
| CHN-080 | Tenant isolation violation |
| CHN-090 | Sync job already running for connector |
| CHN-091 | Retry job source not found |
