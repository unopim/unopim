# External API Integration Research for UnoPim PIM System

**Research Date**: 2026-02-14
**Researcher**: Claude Code Research Agent
**Purpose**: Technical specifications for integrating external e-commerce platforms with Laravel-based UnoPim PIM system

---

## Table of Contents

1. [Salla API (Saudi Arabia E-commerce Platform)](#1-salla-api-saudi-arabia-e-commerce-platform)
2. [Easy Orders API (MENA Logistics/E-commerce)](#2-easy-orders-api-mena-logisticse-commerce)
3. [Shopify Admin API](#3-shopify-admin-api)
4. [Integration Recommendations](#4-integration-recommendations)
5. [Sources](#5-sources)

---

## 1. Salla API (Saudi Arabia E-commerce Platform)

### 1.1 API Version and Base URL

- **API Version**: v2 (Current stable version as of 2026)
- **Base URL**: `https://api.salla.dev/admin/v2`
- **Documentation**: https://docs.salla.dev/

### 1.2 OAuth2 Authentication Flow

#### Authorization Endpoint
- **URL**: `https://accounts.salla.sa/oauth2/auth`
- **Method**: GET
- **Parameters**:
  - `client_id`: Your app's client ID
  - `redirect_uri`: Callback URL
  - `response_type`: `code`
  - `scope`: Required scopes (see below)
  - `state`: CSRF protection token

#### Token Endpoint
- **URL**: `https://accounts.salla.sa/oauth2/token`
- **Method**: POST
- **Grant Types**:
  - `authorization_code`: Initial token exchange
  - `refresh_token`: Token refresh

#### Token Lifecycle
- **Access Token Validity**: 14 days
- **Refresh Token Validity**: 1 month
- **Important**: Always use the latest refresh token for subsequent refresh requests

#### Required Scopes for Product Management
- `offline_access`: Required to generate refresh tokens
- `products.read_write`: Full access to product endpoints
- Additional scopes available for other resources

### 1.3 Product API Endpoints

#### Create Product
```
POST https://api.salla.dev/admin/v2/products
```
**Description**: Create a new product in the Salla store

#### Update Product
```
PUT https://api.salla.dev/admin/v2/products/{product}
```
**Description**: Update specific product details by product ID

#### List Products
```
GET https://api.salla.dev/admin/v2/products
```
**Description**: Retrieve list of products with pagination support

#### Delete Product
```
DELETE https://api.salla.dev/admin/v2/products/{product}
```
**Description**: Remove a product from the store

#### Product Options Management
```
POST https://api.salla.dev/admin/v2/products/{product}/options
PUT https://api.salla.dev/admin/v2/products/options/{option}
```
**Description**: Manage product variants and options

### 1.4 Rate Limits

#### Standard Rate Limiting
- **Plan-Based Limits**: Each store's rate limit depends on its Salla subscription plan
- **Per-Minute Limit**: Maximum number of API calls per 1 minute (varies by plan)
- **Overflow Behavior**: If you exceed the per-minute limit, you can still send **1 request per second** until the minute resets

#### Special Endpoint Limits
- **Customers Endpoint**: Limited to **500 requests per 10 minutes**

#### Rate Limit Headers
Check response headers for:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when the rate limit resets

### 1.5 Webhook Support

#### Webhook Types
Salla provides two categories of webhook events:

1. **App Events**: Automatic events sent to your webhook server related to your Salla App
2. **Store Events**: Events you can subscribe to from the Partners Portal (only selected events will be sent)

#### Webhook Registration
```
POST https://api.salla.dev/admin/v2/webhooks/subscribe
```
**Request Body**:
```json
{
  "url": "https://your-server.com/webhooks/salla",
  "events": ["product.created", "product.updated", "product.deleted"],
  "version": "2"
}
```

#### Webhook Versions
- **Version 2**: Default for all new webhooks (recommended)
- **Version 1**: Legacy version (specify in request parameters if needed)

#### Product-Related Webhook Events
Available product events (exact event names to be confirmed in official documentation):
- Product created
- Product updated
- Product deleted
- Product stock changed
- Product published/unpublished

#### Webhook Verification
- Webhooks include HMAC signatures for security
- Verify using your app's secret key before processing
- Webhook URL must accept POST requests

### 1.6 Multi-Language Support (Arabic RTL Handling)

#### Language Headers
- **Header**: `Accept-Language: AR` (for Arabic)
- **RTL Support**: Salla natively supports Arabic right-to-left text rendering
- **Localization**: Product names, descriptions, and attributes support multilingual content

#### Best Practices
- Store product data in multiple languages in your PIM
- Send appropriate language header in API requests
- Handle RTL text formatting in your UI layer

### 1.7 SAR Pricing / VAT Handling

#### Currency
- **Default Currency**: SAR (Saudi Riyal)
- **Price Format**: Decimal values (e.g., 99.95)

#### VAT (Tax) Support
- **Tax Events**: Webhook event triggered when store tax is created
- **Tax Fields**: Products include tax-related fields in API responses
- **VAT Calculation**: Salla handles VAT calculation based on store settings

### 1.8 PHP SDK and Client Libraries

#### Official Salla Laravel Starter Kit
- **Package**: `salla/laravel-starter-kit`
- **Composer**: `composer require salla/laravel-starter-kit`
- **Repository**: https://github.com/SallaApp/laravel-starter-kit
- **Requirements**:
  - PHP >= 8.1
  - Laravel 9+
  - MySQL database

#### OAuth2 Merchant Client
- **Package**: `salla/oauth2-merchant` (v1.0+)
- **Purpose**: Secure delegated access to Salla Merchant stores
- **Included in**: Laravel Starter Kit

#### Additional Packages
- **ZATCA (Fatoora) Package**: `salla/zatca`
  - Purpose: E-invoicing QR code generation (Saudi e-invoice compliance)
  - Installation: `composer require salla/zatca`

#### Passport Strategy (Optional)
- **Package**: `salla/passport-strategy`
- **Purpose**: Authentication middleware module using OAuth 2.0 API

---

## 2. Easy Orders API (MENA Logistics/E-commerce)

### 2.1 API Base URL and Documentation

- **Base URL**: To be confirmed (not publicly documented in search results)
- **Documentation**: https://public-api-docs.easy-orders.net/docs/

### 2.2 Authentication Method

#### API Key Authentication
- **Method**: API Key in request headers
- **Process**:
  1. Obtain API key from Easy Orders platform
  2. Include API key in request headers
  3. Format: `Authorization: Bearer {api_key}` (format to be verified)

**Note**: Specific authentication endpoint and header format not found in public documentation. Direct access to Easy Orders developer portal required for detailed specifications.

### 2.3 Product/Catalog API Endpoints

#### Available Capabilities
Based on documentation overview:
- Create products for a store
- Update product information
- Manage product catalog
- Update store shipping areas

**Specific Endpoint URLs**: Not available in public search results. Access to Easy Orders API documentation portal required.

### 2.4 Rate Limits

**Status**: Rate limit specifications not publicly documented in search results. Contact Easy Orders support for:
- Requests per minute/second limits
- Burst allowances
- Rate limit headers

### 2.5 Category Mapping Requirements

**Status**: Specific category mapping requirements not found in public documentation.

**Recommended Approach**:
- Contact Easy Orders technical support
- Review API documentation portal
- Test category structure in sandbox environment

### 2.6 Commission Tracking Fields/Endpoints

**Status**: Commission tracking endpoints not identified in public documentation.

**Action Required**:
- Request access to Easy Orders API documentation
- Clarify commission tracking requirements with Easy Orders account manager

### 2.7 PHP SDK

**Status**: No official Easy Orders PHP SDK found in search results.

**Alternative Approach**:
- Use Laravel HTTP client (`Illuminate\Support\Facades\Http`)
- Create custom API wrapper service class
- Implement Guzzle-based HTTP client

**Recommendation**: Build custom Laravel service provider for Easy Orders integration until official SDK is released.

---

## 3. Shopify Admin API

### 3.1 Current Stable API Version

- **Latest Version**: 2026-01
- **GraphQL Endpoint**: `/admin/api/2026-01/graphql.json`
- **REST Endpoint**: `/admin/api/2026-01/{resource}.json`
- **Documentation**: https://shopify.dev/docs/api/admin-graphql/latest

#### Version Support Policy
- **Minimum Support**: 12 months per stable version
- **Migration Window**: 9 months overlap between consecutive versions
- **Deprecation Timeline**: 9 months to migrate before previous version removal

### 3.2 GraphQL Admin API Rate Limits (Cost-Based)

#### Cost-Based Throttling System
Shopify uses a **calculated query cost** method instead of simple request counting.

#### Rate Limit Tiers by Plan

| Plan Tier | Points Per Second | Points Per 60s |
|-----------|------------------|----------------|
| Standard | 50 | 3,000 |
| Advanced | 100 | 6,000 |
| Plus | 500 | 30,000 |

#### Cost Calculation
- **Query (Read)**: 1 point per object fetched
- **Mutation (Write/Update/Delete)**: 10 points per operation
- **Nested Connections**: Additional cost based on depth and breadth

#### Rate Limit Debugging
Include header in requests to see cost breakdown:
```
Shopify-GraphQL-Cost-Debug: 1
```

Response headers include:
- `X-GraphQL-Cost-Include-Fields`: Cost of requested fields
- `X-GraphQL-Cost-Throttle-Status`: Current throttle status

#### Bulk Operations Concurrency
- **API 2026-01+**: Up to **5 concurrent bulk query operations** per shop
- **Earlier Versions**: **1 bulk operation** at a time per shop

### 3.3 REST Admin API Rate Limits

#### Request-Based Throttling
- **Standard Plan**: 2 requests/second (40 requests/minute)
- **Advanced Plan**: 4 requests/second (80 requests/minute)
- **Plus Plan**: Higher limits (contact Shopify for specifics)

#### Rate Limit Headers
```
X-Shopify-Shop-Api-Call-Limit: 32/40
```
Format: `current_requests/max_requests` in 60-second window

### 3.4 Bulk Operation Mutation Patterns

#### GraphQL Bulk Operations Overview
For large-scale data operations (e.g., syncing entire product catalogs), use bulk operations to avoid rate limit issues.

#### Bulk Query Process

**Step 1: Initiate Bulk Operation**
```graphql
mutation {
  bulkOperationRunQuery(
    query: """
    {
      products {
        edges {
          node {
            id
            title
            variants {
              edges {
                node {
                  id
                  price
                  inventoryQuantity
                }
              }
            }
          }
        }
      }
    }
    """
  ) {
    bulkOperation {
      id
      status
    }
    userErrors {
      field
      message
    }
  }
}
```

**Step 2: Subscribe to Webhook**
```graphql
mutation {
  webhookSubscriptionCreate(
    topic: BULK_OPERATIONS_FINISH
    webhookSubscription: {
      format: JSON
      callbackUrl: "https://your-app.com/webhooks/bulk-finish"
    }
  ) {
    webhookSubscription {
      id
    }
  }
}
```

**Step 3: Poll or Wait for Webhook**
```graphql
query {
  currentBulkOperation {
    id
    status
    errorCode
    createdAt
    completedAt
    objectCount
    fileSize
    url
    partialDataUrl
  }
}
```

**Step 4: Download Results**
- Results delivered as JSONL (JSON Lines) format
- Download from `url` field
- File is gzip compressed

#### Bulk Operation Constraints
- **Maximum Connections**: 5 total connections in query
- **Maximum Depth**: 2 levels of nested connections
- **Concurrent Operations**: 5 bulk queries at a time (API 2026-01+)

### 3.5 Webhook HMAC Verification Details

#### HMAC Header
- **Header Name**: `X-Shopify-Hmac-SHA256`
- **PHP Header**: `HTTP_X_SHOPIFY_HMAC_SHA256`
- **Encoding**: Base64-encoded SHA256 HMAC

#### PHP Verification Example

```php
<?php

namespace App\Services\Shopify;

class WebhookValidator
{
    /**
     * Verify Shopify webhook HMAC signature
     *
     * @param string $rawBody Raw request body (BEFORE parsing)
     * @param string $hmacHeader Value of X-Shopify-Hmac-SHA256 header
     * @param string $appSecret Your Shopify app's client secret
     * @return bool
     */
    public function verifyWebhook(string $rawBody, string $hmacHeader, string $appSecret): bool
    {
        // Calculate HMAC using raw body
        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $rawBody, $appSecret, true)
        );

        // Constant-time comparison to prevent timing attacks
        return hash_equals($calculatedHmac, $hmacHeader);
    }
}
```

#### Laravel Implementation

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyShopifyWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $appSecret = config('services.shopify.client_secret');

        // Get raw request body (CRITICAL: must be raw, not parsed)
        $rawBody = $request->getContent();

        $calculatedHmac = base64_encode(
            hash_hmac('sha256', $rawBody, $appSecret, true)
        );

        if (!hash_equals($calculatedHmac, $hmacHeader)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
```

#### Important HMAC Verification Notes

1. **Use Raw Body**: MUST use raw request body before JSON parsing
2. **Avoid Body Parser Middleware**: Capture raw body before middleware processes it
3. **Constant-Time Comparison**: Use `hash_equals()` to prevent timing attacks
4. **GraphiQL Subscriptions**: Webhooks created via GraphiQL may have HMAC mismatches (use code-based subscription instead)

#### Webhook Best Practices
- Accept webhook immediately (respond with 200 OK within 5 seconds)
- Process payload in background queue
- Verify HMAC before queuing
- Handle idempotency (webhooks may be sent multiple times)
- Store webhook ID to prevent duplicate processing

### 3.6 Product Sync Best Practices for Large Catalogs

#### Strategy 1: Bulk Operations (Recommended)

**When to Use**:
- Initial catalog sync (500+ products)
- Full catalog refresh
- Large-scale updates

**Advantages**:
- Bypasses rate limits
- Single compressed JSONL file download
- 2.8-4.4x faster than REST API
- No need to paginate

**Process**:
1. Initiate bulk query for all products
2. Subscribe to `BULK_OPERATIONS_FINISH` webhook
3. Download JSONL results file when complete
4. Parse and import into UnoPim PIM

#### Strategy 2: GraphQL Pagination (Medium Catalogs)

**When to Use**:
- 50-500 products
- Real-time syncing
- Selective product updates

**Example Query**:
```graphql
query ($cursor: String) {
  products(first: 250, after: $cursor) {
    pageInfo {
      hasNextPage
      endCursor
    }
    edges {
      cursor
      node {
        id
        title
        descriptionHtml
        vendor
        productType
        tags
        variants(first: 100) {
          edges {
            node {
              id
              title
              price
              sku
              inventoryQuantity
            }
          }
        }
        metafields(first: 50) {
          edges {
            node {
              namespace
              key
              value
              type
            }
          }
        }
      }
    }
  }
}
```

#### Strategy 3: Webhooks (Real-Time Sync)

**When to Use**:
- Ongoing synchronization
- Real-time updates after initial sync
- Event-driven architecture

**Product Webhook Topics**:
- `PRODUCTS_CREATE`
- `PRODUCTS_UPDATE`
- `PRODUCTS_DELETE`
- `INVENTORY_LEVELS_UPDATE`
- `PRODUCT_PUBLICATIONS_CREATE`
- `PRODUCT_PUBLICATIONS_DELETE`

**Implementation**:
```graphql
mutation {
  webhookSubscriptionCreate(
    topic: PRODUCTS_UPDATE
    webhookSubscription: {
      format: JSON
      callbackUrl: "https://unopim.example.com/webhooks/shopify/products"
      includeFields: ["id", "title", "variants", "metafields"]
    }
  ) {
    webhookSubscription {
      id
      topic
      endpoint {
        __typename
        ... on WebhookHttpEndpoint {
          callbackUrl
        }
      }
    }
  }
}
```

#### Metafields Handling

**Best Practices**:
1. **Namespace Organization**: Use consistent namespaces (e.g., `unopim.product_data`)
2. **Type Definitions**: Create metafield definitions for validation
3. **Bulk Editing**: Use GraphQL mutations for bulk metafield updates
4. **Backup Before Updates**: Export metafields before major changes (30-day recovery window)

**Bulk Metafield Update Example**:
```graphql
mutation {
  productUpdate(input: {
    id: "gid://shopify/Product/123456789"
    metafields: [
      {
        namespace: "unopim"
        key: "pim_id"
        value: "UNO-PROD-001"
        type: "single_line_text_field"
      }
    ]
  }) {
    product {
      id
      metafields(first: 10) {
        edges {
          node {
            namespace
            key
            value
          }
        }
      }
    }
  }
}
```

#### Performance Optimization Tips

1. **Work During Low-Traffic Hours**: Schedule bulk operations for early morning or late evening
2. **Batch Size**: Request 250 products per page (GraphQL max)
3. **Test on Small Batches**: Verify logic with 5-10 products before scaling
4. **Export Backups**: Always backup current data before bulk operations
5. **Connection Limits**: Respect GraphQL connection depth limits (max 2 levels)
6. **Monitor Costs**: Use `Shopify-GraphQL-Cost-Debug` header to optimize queries

#### Error Handling

```php
// Example Laravel service for Shopify product sync
class ShopifyProductSyncService
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 2; // seconds

    public function syncProduct(string $productId): void
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->shopifyClient->query($productQuery);

                if (isset($response['errors'])) {
                    throw new ShopifyApiException($response['errors']);
                }

                $this->importToPim($response['data']['product']);
                return;

            } catch (RateLimitException $e) {
                // Wait for rate limit reset
                sleep($this->retryDelay * pow(2, $attempt)); // Exponential backoff
                $attempt++;

            } catch (ShopifyApiException $e) {
                Log::error('Shopify API error', [
                    'product_id' => $productId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        throw new MaxRetriesExceededException("Failed to sync product after {$this->maxRetries} attempts");
    }
}
```

### 3.7 Additional Shopify Features

#### API Version Changes in 2026-01

**New Features**:
- **Enhanced Metafield Querying**: Greater than/less than comparisons, prefix matching, boolean operators (AND, OR, NOT)
- **Mandatory Idempotency** (April 2026): Required for inventory adjustments and refund mutations
- **Improved Bulk Operations**: Up to 5 concurrent bulk queries per shop

**Migration Timeline**:
- **Support Until**: 2027-01 (minimum 12 months)
- **Migration Deadline**: 2026-10 (9 months after 2026-01 release)

---

## 4. Integration Recommendations

### 4.1 Priority Integration Matrix

| Platform | Integration Complexity | Data Availability | Recommended Approach |
|----------|----------------------|-------------------|---------------------|
| Salla | **Medium** | Excellent (full docs + SDK) | Official Laravel package + custom sync service |
| Easy Orders | **High** | Limited (docs access required) | Custom HTTP client wrapper (pending docs access) |
| Shopify | **Low-Medium** | Excellent (comprehensive docs) | Laravel Shopify package + GraphQL bulk operations |

### 4.2 Recommended Laravel Package Architecture

```
packages/Webkul/Integrations/
├── src/
│   ├── Config/
│   │   └── integrations.php
│   ├── Contracts/
│   │   ├── IntegrationInterface.php
│   │   ├── ProductSyncInterface.php
│   │   └── WebhookHandlerInterface.php
│   ├── Services/
│   │   ├── Salla/
│   │   │   ├── SallaAuthService.php
│   │   │   ├── SallaProductService.php
│   │   │   └── SallaWebhookHandler.php
│   │   ├── EasyOrders/
│   │   │   ├── EasyOrdersClient.php
│   │   │   └── EasyOrdersProductService.php
│   │   └── Shopify/
│   │       ├── ShopifyGraphQLClient.php
│   │       ├── ShopifyProductSyncService.php
│   │       ├── ShopifyBulkOperationService.php
│   │       └── ShopifyWebhookHandler.php
│   ├── Models/
│   │   ├── Integration.php
│   │   ├── IntegrationLog.php
│   │   └── ProductMapping.php
│   ├── Jobs/
│   │   ├── SyncProductToSalla.php
│   │   ├── SyncProductToShopify.php
│   │   └── ProcessBulkOperationResults.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── WebhookController.php
│   │   └── Middleware/
│   │       ├── VerifySallaWebhook.php
│   │       └── VerifyShopifyWebhook.php
│   └── Routes/
│       ├── api.php
│       └── webhooks.php
└── tests/
    ├── Unit/
    └── Feature/
```

### 4.3 Database Schema Recommendations

```sql
-- Integration credentials and configuration
CREATE TABLE integrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('salla', 'shopify', 'easy_orders') NOT NULL,
    credentials JSON NOT NULL,
    settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Product mapping between PIM and external platforms
CREATE TABLE product_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id BIGINT UNSIGNED NOT NULL,
    pim_product_id BIGINT UNSIGNED NOT NULL,
    external_product_id VARCHAR(255) NOT NULL,
    external_variant_id VARCHAR(255) NULL,
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    last_synced_at TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (integration_id, pim_product_id, external_product_id),
    INDEX idx_pim_product (pim_product_id),
    INDEX idx_external_product (external_product_id)
);

-- Integration activity logs
CREATE TABLE integration_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id BIGINT UNSIGNED NOT NULL,
    operation VARCHAR(50) NOT NULL,
    status ENUM('success', 'warning', 'error') NOT NULL,
    message TEXT,
    request_payload JSON,
    response_payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    INDEX idx_integration_status (integration_id, status),
    INDEX idx_created_at (created_at)
);

-- Webhook event tracking (idempotency)
CREATE TABLE webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integration_id BIGINT UNSIGNED NOT NULL,
    webhook_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_webhook (integration_id, webhook_id),
    INDEX idx_event_type (event_type),
    INDEX idx_processed (processed_at)
);
```

### 4.4 Configuration Management

**packages/Webkul/Integrations/src/Config/integrations.php**

```php
<?php

return [
    'salla' => [
        'base_url' => env('SALLA_API_BASE_URL', 'https://api.salla.dev/admin/v2'),
        'auth_url' => env('SALLA_AUTH_URL', 'https://accounts.salla.sa/oauth2'),
        'client_id' => env('SALLA_CLIENT_ID'),
        'client_secret' => env('SALLA_CLIENT_SECRET'),
        'redirect_uri' => env('SALLA_REDIRECT_URI'),
        'scopes' => ['offline_access', 'products.read_write'],
        'rate_limit' => [
            'max_requests_per_minute' => 60, // Adjust based on plan
            'customers_limit' => 500, // 500 per 10 minutes
        ],
    ],

    'shopify' => [
        'api_version' => env('SHOPIFY_API_VERSION', '2026-01'),
        'client_id' => env('SHOPIFY_CLIENT_ID'),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
        'scopes' => ['read_products', 'write_products', 'read_inventory', 'write_inventory'],
        'rate_limit' => [
            'graphql_points_per_second' => 50, // Standard plan
            'rest_requests_per_second' => 2, // Standard plan
        ],
        'bulk_operations' => [
            'max_concurrent' => 5,
            'webhook_url' => env('APP_URL') . '/webhooks/shopify/bulk-finish',
        ],
    ],

    'easy_orders' => [
        'base_url' => env('EASY_ORDERS_API_BASE_URL'),
        'api_key' => env('EASY_ORDERS_API_KEY'),
        'rate_limit' => [
            'max_requests_per_minute' => 60, // To be confirmed
        ],
    ],

    // Global sync settings
    'sync' => [
        'batch_size' => 250,
        'max_retries' => 3,
        'retry_delay' => 2, // seconds
        'queue' => 'integrations',
        'timeout' => 300, // seconds
    ],

    // Field mappings
    'field_mappings' => [
        'product' => [
            'title' => 'name',
            'description' => 'description',
            'sku' => 'sku',
            'price' => 'price',
            'inventory_quantity' => 'quantity',
        ],
    ],
];
```

### 4.5 Next Steps

#### Immediate Actions

1. **Salla Integration**:
   - ✅ Install `salla/laravel-starter-kit` via Composer
   - ✅ Configure OAuth2 credentials in `.env`
   - ✅ Implement `SallaProductService` with CRUD operations
   - ✅ Set up webhook endpoint with HMAC verification
   - ✅ Test with sandbox/development Salla store

2. **Shopify Integration**:
   - ✅ Install recommended Laravel Shopify package (e.g., `osiset/laravel-shopify`)
   - ✅ Configure GraphQL client for API 2026-01
   - ✅ Implement bulk operation workflow for initial sync
   - ✅ Set up webhook handlers with HMAC verification
   - ✅ Test metafield synchronization

3. **Easy Orders Integration**:
   - ⚠️ **Action Required**: Request access to Easy Orders API documentation
   - ⚠️ Contact Easy Orders support for:
     - API credentials
     - Rate limit specifications
     - Category mapping requirements
     - Commission tracking endpoints
   - 🔄 Build custom HTTP client wrapper once docs are available

#### Development Priorities

**Phase 1**: Foundation (Week 1-2)
- Create `Webkul\Integrations` package structure
- Implement database migrations
- Build abstract `IntegrationInterface`
- Set up logging and monitoring

**Phase 2**: Salla Integration (Week 3-4)
- OAuth2 flow implementation
- Product CRUD operations
- Webhook processing
- Testing and validation

**Phase 3**: Shopify Integration (Week 5-6)
- GraphQL client setup
- Bulk operation sync
- Webhook handlers
- Metafield management

**Phase 4**: Easy Orders Integration (Week 7-8)
- Pending API documentation access
- Custom client implementation
- Product sync logic
- Commission tracking

**Phase 5**: Testing & Optimization (Week 9-10)
- End-to-end integration tests
- Performance optimization
- Error handling refinement
- Documentation

---

## 5. Sources

### Salla API Sources
- [Salla API Authorization Documentation](https://docs.salla.dev/421118m0)
- [Salla API Get Started Guide](https://docs.salla.dev/421117m0)
- [Salla Partners Documentation Portal](https://docs.salla.dev/)
- [Salla API Rate Limiting Documentation](https://docs.salla.dev/421125m0)
- [Salla Webhooks Documentation](https://docs.salla.dev/421119m0)
- [Salla Register Webhook Endpoint](https://docs.salla.dev/5394134e0)
- [Salla App Events Documentation](https://docs.salla.dev/421413m0)
- [Salla Update Product Endpoint](https://docs.salla.dev/5394170e0)
- [Salla Create Product Endpoint](https://docs.salla.dev/5394167e0)
- [Salla OAuth2 Merchant GitHub Repository](https://github.com/SallaApp/oauth2-merchant)
- [Salla Laravel Starter Kit GitHub](https://github.com/SallaApp/laravel-starter-kit)
- [Salla Laravel Starter Kit on Packagist](https://packagist.org/packages/salla/laravel-starter-kit)
- [Salla OAuth 2.0 In Action Blog Post](https://salla.dev/blog/oauth-2-0-in-action-with-salla/)
- [Salla API Comprehensive Guide by API2Cart](https://api2cart.com/api-technology/salla-api/)

### Easy Orders API Sources
- [EasyOrders API Introduction](https://public-api-docs.easy-orders.net/docs/intro)
- [EasyOrders API Authentication](https://public-api-docs.easy-orders.net/docs/authentication)

### Shopify API Sources
- [Shopify GraphQL Admin API Reference](https://shopify.dev/docs/api/admin-graphql/latest)
- [Shopify REST Admin API Rate Limits](https://shopify.dev/docs/api/admin-rest/usage/rate-limits)
- [Shopify API Usage Limits](https://shopify.dev/docs/api/usage/limits)
- [Shopify API Versioning Guide](https://shopify.dev/docs/api/usage/versioning)
- [Shopify GraphQL Rate Limits Blog](https://www.shopify.com/partners/blog/graphql-rate-limits)
- [Shopify Rate Limiting by Query Complexity](https://shopify.engineering/rate-limiting-graphql-apis-calculating-query-complexity)
- [Shopify Bulk Operations Documentation](https://shopify.dev/docs/api/usage/bulk-operations/queries)
- [Shopify Webhooks HTTPS Delivery Guide](https://shopify.dev/docs/apps/build/webhooks/subscribe/https)
- [Shopify HMAC Verification Medium Article](https://medium.com/@SonuTechWeb/how-to-receive-webhook-response-of-shopify-and-verify-in-php-73d6e1946e2b)
- [Shopify HMAC Verification Best Practices](https://shaunagordon.com/2017/08/24/shopify-hmac-verification/)
- [Ultimate Guide to Syncing Data from Shopify](https://gadget.dev/blog/the-ultimate-guide-to-syncing-data-from-shopify)
- [Shopify Bulk Edit Metafields Help Center](https://help.shopify.com/en/manual/custom-data/metafields/bulk-edit-metafields)
- [Shopify Product Unidirectional Sync](https://shopify.dev/docs/apps/build/sales-channels/product-sync)
- [Shopify 2025-01 Release Notes](https://shopify.dev/docs/api/release-notes/2025-01)
- [Shopify Flow Adopts 2026-01 GraphQL API](https://changelog.shopify.com/posts/flow-adopts-version-2026-01-of-the-graphql-admin-api)
- [Shopify Changelog](https://changelog.shopify.com/)
- [Time-Saving Methods to Bulk Edit Shopify Metafields](https://barn2.com/blog/shopify-bulk-edit-metafields/)
- [Shopify Bulk Operations Perform Guide](https://shopify.dev/docs/api/usage/bulk-operations/queries)

### Additional Reference Sources
- [API Rate Limiting Best Practices by Postman](https://blog.postman.com/what-is-api-rate-limiting/)
- [API Rate Limiting Guide by Gcore](https://gcore.com/learning/api-rate-limiting)
- [SHA256 Webhook Signature Verification Guide](https://hookdeck.com/webhooks/guides/how-to-implement-sha256-webhook-signature-verification)

---

**End of Research Document**

*This research document provides concrete technical specifications for integrating Salla, Easy Orders, and Shopify APIs with the UnoPim Laravel-based PIM system. For implementation assistance, refer to the recommended package architecture and database schema sections.*
