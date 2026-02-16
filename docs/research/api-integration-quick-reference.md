# External API Integration Quick Reference

**Last Updated**: 2026-02-14

---

## Salla API (Saudi Arabia E-commerce)

### Connection Details
```
Base URL: https://api.salla.dev/admin/v2
Auth URL: https://accounts.salla.sa/oauth2
API Version: v2
```

### Authentication
```bash
# OAuth2 Flow
1. Authorization: GET https://accounts.salla.sa/oauth2/auth
2. Token Exchange: POST https://accounts.salla.sa/oauth2/token
3. Scopes: offline_access, products.read_write
4. Access Token: 14 days validity
5. Refresh Token: 1 month validity
```

### Key Endpoints
```
POST   /products                    # Create product
GET    /products                    # List products
PUT    /products/{id}               # Update product
DELETE /products/{id}               # Delete product
POST   /webhooks/subscribe          # Register webhook
```

### Rate Limits
- Plan-dependent (per minute)
- Overflow: 1 request/second
- Customers endpoint: 500/10 minutes

### Laravel Package
```bash
composer require salla/laravel-starter-kit
# Includes: salla/oauth2-merchant (v1.0+)
# Requirements: PHP >= 8.1, Laravel 9+
```

### Webhook Events
- product.created
- product.updated
- product.deleted
- Verification: HMAC signature

### MENA-Specific
- Multi-language: `Accept-Language: AR`
- RTL support: Native
- Currency: SAR (Saudi Riyal)
- VAT: Supported in product fields
- E-invoice: `salla/zatca` package

---

## Shopify Admin API

### Connection Details
```
API Version: 2026-01
GraphQL: /admin/api/2026-01/graphql.json
REST: /admin/api/2026-01/{resource}.json
```

### Rate Limits

#### GraphQL (Cost-Based)
| Plan     | Points/Second | Points/60s |
|----------|---------------|------------|
| Standard | 50            | 3,000      |
| Advanced | 100           | 6,000      |
| Plus     | 500           | 30,000     |

**Costs**: Query = 1 point/object, Mutation = 10 points

#### REST (Request-Based)
- Standard: 2 req/s (40/min)
- Advanced: 4 req/s (80/min)

### Bulk Operations
- Max concurrent: 5 queries (API 2026-01+)
- Max connections: 5 per query
- Max depth: 2 levels
- Format: JSONL (gzipped)
- Webhook: `BULK_OPERATIONS_FINISH`

### Webhook HMAC Verification (PHP)
```php
$calculatedHmac = base64_encode(
    hash_hmac('sha256', $rawBody, $appSecret, true)
);

if (!hash_equals($calculatedHmac, $hmacHeader)) {
    abort(401, 'Invalid signature');
}
```

### Product Webhook Topics
- `PRODUCTS_CREATE`
- `PRODUCTS_UPDATE`
- `PRODUCTS_DELETE`
- `INVENTORY_LEVELS_UPDATE`

### Best Practices
- Use bulk operations for 500+ products
- GraphQL pagination for 50-500 products
- Webhooks for real-time sync
- Backup metafields before bulk edits
- Test on 5-10 products first
- Schedule large ops during low-traffic hours

---

## Easy Orders API (MENA Logistics)

### Connection Details
```
Base URL: TBD (documentation access required)
Documentation: https://public-api-docs.easy-orders.net/docs/
```

### Authentication
- Method: API Key in headers
- Format: To be confirmed

### Status
⚠️ **Limited Public Documentation**

**Action Required**:
1. Request API documentation access
2. Obtain API credentials
3. Clarify rate limits
4. Confirm category mapping requirements
5. Verify commission tracking endpoints

### Integration Approach
- Build custom HTTP client wrapper
- Use Laravel HTTP facade
- Create service provider
- Wait for official PHP SDK

---

## Integration Comparison Matrix

| Feature | Salla | Shopify | Easy Orders |
|---------|-------|---------|-------------|
| **Documentation** | ✅ Excellent | ✅ Excellent | ⚠️ Limited |
| **PHP SDK** | ✅ Laravel Kit | ✅ Community | ❌ None |
| **OAuth2** | ✅ Yes | ✅ Yes | ⚠️ TBD |
| **Webhooks** | ✅ Yes | ✅ Yes | ⚠️ TBD |
| **Rate Limits** | Plan-based | Tier-based | ⚠️ TBD |
| **Bulk Ops** | Standard | ✅ GraphQL | ⚠️ TBD |
| **MENA Focus** | ✅ Saudi | 🌍 Global | ✅ MENA |

---

## Laravel Implementation Checklist

### Package Structure
```
packages/Webkul/Integrations/
├── src/
│   ├── Services/
│   │   ├── Salla/
│   │   ├── Shopify/
│   │   └── EasyOrders/
│   ├── Models/
│   ├── Jobs/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Routes/
└── tests/
```

### Database Tables
- `integrations` - Credentials & config
- `product_mappings` - PIM ↔ External mapping
- `integration_logs` - Activity tracking
- `webhook_events` - Idempotency tracking

### Configuration
```bash
# .env
SALLA_CLIENT_ID=
SALLA_CLIENT_SECRET=
SALLA_REDIRECT_URI=

SHOPIFY_CLIENT_ID=
SHOPIFY_CLIENT_SECRET=
SHOPIFY_API_VERSION=2026-01

EASY_ORDERS_API_BASE_URL=
EASY_ORDERS_API_KEY=
```

### Queue Setup
```bash
# config/queue.php
'integrations' => [
    'driver' => 'database',
    'table' => 'jobs',
    'queue' => 'integrations',
    'retry_after' => 300,
],
```

---

## Development Timeline

| Phase | Duration | Focus |
|-------|----------|-------|
| 1 | Week 1-2 | Foundation & package structure |
| 2 | Week 3-4 | Salla integration |
| 3 | Week 5-6 | Shopify integration |
| 4 | Week 7-8 | Easy Orders (pending docs) |
| 5 | Week 9-10 | Testing & optimization |

---

## Recommended Packages

### Salla
```bash
composer require salla/laravel-starter-kit
composer require salla/zatca  # E-invoicing (optional)
```

### Shopify
```bash
composer require osiset/laravel-shopify
# OR
composer require gnikyt/laravel-shopify
```

### HTTP Client (for Easy Orders)
```bash
# Laravel's built-in HTTP client is sufficient
# Use: Illuminate\Support\Facades\Http
```

---

## Testing Endpoints

### Salla Sandbox
- Request sandbox store from Salla Partners Portal
- Use test OAuth2 credentials
- Webhook testing: Use ngrok or expose local URL

### Shopify Development Store
- Create free development store: partners.shopify.com
- Install custom app
- Use GraphiQL for query testing

### Easy Orders
- Request sandbox environment access
- Confirm test API credentials

---

## Security Checklist

- ✅ Verify all webhook HMAC signatures
- ✅ Store credentials in `.env`, not in code
- ✅ Use HTTPS for webhook endpoints
- ✅ Implement rate limiting on webhook handlers
- ✅ Log all integration activity
- ✅ Validate all external data before storing
- ✅ Use constant-time comparison for HMAC (`hash_equals`)
- ✅ Implement webhook idempotency checks
- ✅ Queue webhook processing (respond with 200 OK immediately)
- ✅ Set up monitoring alerts for integration failures

---

## Error Handling Strategy

### Rate Limit Errors
```php
try {
    $response = $client->request();
} catch (RateLimitException $e) {
    // Exponential backoff
    sleep(2 ** $attempt);
    retry();
}
```

### Webhook Processing
```php
public function handle(Request $request)
{
    // 1. Verify HMAC immediately
    if (!$this->verifyHmac($request)) {
        return response('Unauthorized', 401);
    }

    // 2. Check idempotency
    if ($this->isDuplicate($request->header('X-Webhook-ID'))) {
        return response('Already processed', 200);
    }

    // 3. Queue processing
    ProcessWebhook::dispatch($request->all());

    // 4. Respond immediately
    return response('OK', 200);
}
```

### Sync Failures
```php
// Log and retry
Log::error('Product sync failed', [
    'integration' => 'salla',
    'product_id' => $productId,
    'error' => $e->getMessage(),
]);

// Queue for retry
SyncProductToSalla::dispatch($product)
    ->delay(now()->addMinutes(5))
    ->onQueue('integrations');
```

---

## Monitoring & Logging

### Key Metrics
- Sync success/failure rate
- Average sync time
- API rate limit usage
- Webhook processing time
- Queue backlog size

### Log Events
- All API requests/responses
- Webhook receipts
- Sync operations
- Authentication refreshes
- Rate limit hits
- Errors and exceptions

### Alerts
- Integration downtime
- High error rate (>5%)
- Rate limit approaching (>80%)
- Queue processing delays (>5 min)
- Webhook signature failures

---

**For detailed implementation guidance, see: `/docs/research/external-api-integration-research.md`**
