# Order Package Services & ValueObjects - Implementation Summary

Created: 2026-02-17

## Overview
Comprehensive Services and ValueObjects for the Order package following UnoPim architectural patterns and integrating with the Pricing package for cost/profit calculations.

---

## Services (3 files in `src/Services/`)

### 1. OrderSyncService.php (13KB)
**Purpose**: Order synchronization from external channels to unified orders

**Key Methods**:
- `syncOrderFromChannel(Channel, array): UnifiedOrder` - Sync single order
- `syncBatchOrders(Channel, array): array` - Batch synchronization
- `validateOrderData(array): bool` - Validate order structure
- `mapChannelOrderToUnified(string, array): array` - Channel-specific mapping
- `logSyncEvent(string, array): OrderSyncLog` - Event logging
- `handleSyncFailure(Channel, array, Throwable): void` - Error handling

**Supported Channels**: Shopify, Salla, WooCommerce, Magento

**Features**:
- Transaction safety with DB::beginTransaction/commit/rollBack
- Comprehensive validation (email format, numeric amounts)
- Status normalization (maps 15+ channel statuses to 5 unified statuses)
- Detailed logging for success/failure
- Idempotent sync (create or update based on existence)

---

### 2. ProfitabilityCalculator.php (14KB)
**Purpose**: Profit and margin analysis for orders and products

**Key Methods**:
- `calculateOrderProfitability(UnifiedOrder): ProfitabilityResult` - Full order analysis
- `calculateItemProfitability(UnifiedOrderItem): array` - Line item analysis
- `calculateMarginPercentage(float, float): float` - Margin calculation
- `aggregateProfitByChannel(Channel, Carbon, Carbon): array` - Channel-level aggregation
- `aggregateProfitByProduct(Product, Carbon, Carbon): array` - Product-level aggregation
- `getTopProfitableProducts(int, array): Collection` - Top N profitable products

**Integration**:
- Uses `ProductCostRepository` from Pricing package
- Retrieves 5 cost types: cogs, operational, shipping, overhead, marketing
- Caching with 10-minute TTL for performance

**Features**:
- Real-time profitability calculation
- Multi-level aggregation (order, channel, product)
- Tenant-aware caching
- Comprehensive logging

---

### 3. WebhookProcessor.php (13KB)
**Purpose**: Process incoming webhook events from external channels

**Key Methods**:
- `processWebhookEvent(OrderWebhook, array): void` - Main webhook handler
- `verifyWebhookSignature(string, string, string): bool` - HMAC SHA256 verification
- `dispatchWebhookEvent(string, array): void` - Event routing
- `handleOrderCreated(array): void` - order.created handler
- `handleOrderUpdated(array): void` - order.updated handler
- `handleOrderCancelled(array): void` - order.cancelled handler
- `handleOrderFulfilled(array): void` - order.fulfilled handler
- `handleOrderRefunded(array): void` - order.refunded handler
- `retryFailedWebhook(OrderWebhook): void` - Retry logic

**Supported Events**: order.created, order.updated, order.cancelled, order.fulfilled, order.refunded

**Features**:
- HMAC SHA256 signature verification
- Laravel event dispatching for extensibility
- Automatic retry (max 3 attempts)
- Status tracking (pending → processing → processed/failed)
- Integration with OrderSyncService for order data sync

---

## ValueObjects (3 files in `src/ValueObjects/`)

### 1. ProfitabilityResult.php (6.2KB)
**Purpose**: Immutable value object for profitability calculation results

**Properties** (all readonly):
- `float $totalRevenue` - Total revenue from order(s)
- `float $totalCost` - Total cost of goods and operations
- `float $totalProfit` - Net profit (revenue - cost)
- `float $marginPercentage` - Profit margin percentage (0-100)
- `array $itemBreakdown` - Detailed breakdown per order item
- `Carbon $calculatedAt` - Timestamp of calculation

**Helper Methods**:
- `isProfitable(): bool` - Check if profitable
- `isLoss(): bool` - Check if loss
- `isBreakEven(float): bool` - Check if break-even (with tolerance)
- `getMarginTier(): string` - Tier classification (loss/low/average/good/excellent)
- `getMostProfitableItem(): ?array` - Most profitable line item
- `getLeastProfitableItem(): ?array` - Least profitable line item
- `getReturnOnCost(): float` - ROC percentage
- `getSummary(): string` - Human-readable summary

**Factory Methods**:
- `fromOrder(UnifiedOrder, array): self` - Create from order and breakdown

**Implements**: Arrayable, JsonSerializable

---

### 2. SyncResult.php (6.4KB)
**Purpose**: Immutable value object for order synchronization results

**Properties** (all readonly):
- `bool $success` - Whether sync succeeded
- `?UnifiedOrder $order` - Synchronized order (null if failed)
- `?OrderSyncLog $syncLog` - Sync log entry
- `array $errors` - Error messages/details
- `Carbon $syncedAt` - Timestamp of sync attempt

**Helper Methods**:
- `hasErrors(): bool` - Check if errors exist
- `getFirstError(): ?string` - Get first error message
- `getErrorMessages(): array` - All error messages as array
- `getOrderId(): ?int` - Order ID if successful
- `getChannelOrderId(): ?string` - Channel order ID
- `getSyncLogId(): ?int` - Sync log ID
- `wasCreated(): bool` - Check if order was created
- `wasUpdated(): bool` - Check if order was updated
- `getStatusMessage(): string` - Human-readable status
- `toLogArray(): array` - Compact representation for logging

**Factory Methods**:
- `success(UnifiedOrder, ?OrderSyncLog): self` - Create success result
- `failure(array, ?OrderSyncLog): self` - Create failure result

**Implements**: Arrayable, JsonSerializable

---

### 3. WebhookEventPayload.php (8.4KB)
**Purpose**: Immutable value object for webhook event payloads

**Properties** (all readonly):
- `string $eventType` - Webhook event type (e.g., order.created)
- `string $channelOrderId` - Order ID from external channel
- `int $channelId` - Internal channel ID
- `array $orderData` - Complete order data from channel
- `?string $signature` - Optional webhook signature
- `Carbon $timestamp` - When webhook event occurred

**Helper Methods**:
- `hasSignature(): bool` - Check if signature present
- `getEventCategory(): string` - Event category (e.g., "order")
- `getEventAction(): string` - Event action (e.g., "created")
- `isOrderEvent(): bool` - Check if order-related event
- `getRawJsonPayload(): string` - JSON payload for signature verification
- `getCustomerEmail(): ?string` - Extract customer email
- `getOrderTotal(): ?float` - Extract order total
- `getCurrencyCode(): ?string` - Extract currency code
- `toLogArray(): array` - Compact representation for logging

**Factory Methods**:
- `fromRequest(Request): self` - Create from HTTP request
- `fromArray(array): self` - Create from array (for testing)

**Implements**: Arrayable, JsonSerializable

**Supported Headers**:
- Event type: X-Event-Type, X-Webhook-Event, X-Shopify-Topic
- Channel ID: X-Channel-Id (or in body)
- Signature: X-Webhook-Signature, X-Shopify-Hmac-Sha256, X-Salla-Signature

---

## Architecture Patterns

### Service Layer
- Constructor dependency injection
- Type hints for all parameters and return types
- Comprehensive DocBlocks following PHPDoc standards
- Exception handling with descriptive messages
- Laravel facades (DB, Log, Cache, Event)
- Repository pattern for data access

### Value Objects
- `readonly` keyword for immutability (PHP 8.2+)
- Named constructor parameters for clarity
- Factory methods for common creation patterns
- Rich domain behavior (helper methods)
- Implements Arrayable and JsonSerializable
- Fluent interface for chaining

### Integration
- **Pricing Package**: ProductCostRepository for cost data retrieval
- **Core Package**: Channel model, ChannelRepository
- **Product Package**: Product model
- **Order Package**: UnifiedOrder, UnifiedOrderItem, OrderSyncLog, OrderWebhook models

### Logging Strategy
- Info level: Successful operations, aggregations
- Debug level: Detailed calculation data, cache operations
- Warning level: Batch failures (non-critical), signature mismatches
- Error level: Critical failures, exceptions

### Caching Strategy
- Order profitability: 10 minutes TTL
- Channel/Product aggregations: 10 minutes TTL
- Tenant-aware cache keys
- Cache invalidation on cost updates (handled by Pricing package)

---

## Dependencies

```php
// Pricing Package
use Webkul\Pricing\Repositories\ProductCostRepository;

// Order Package
use Webkul\Order\Models\{UnifiedOrder, UnifiedOrderItem, OrderSyncLog, OrderWebhook};
use Webkul\Order\Repositories\{UnifiedOrderRepository, OrderSyncLogRepository, OrderWebhookRepository};
use Webkul\Order\ValueObjects\{ProfitabilityResult, SyncResult, WebhookEventPayload};

// Core Package
use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\ChannelRepository;

// Product Package
use Webkul\Product\Models\Product;

// Laravel
use Carbon\Carbon;
use Illuminate\Support\{Collection, Facades\Cache, Facades\DB, Facades\Event, Facades\Log};
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
```

---

## Testing Recommendations

### Unit Tests
1. **OrderSyncService**
   - Test validation logic for all required fields
   - Test status normalization for all channel types
   - Test channel-specific mapping transformations
   - Mock repositories and verify method calls
   - Test error handling and logging

2. **ProfitabilityCalculator**
   - Test margin calculation edge cases (zero revenue, negative costs)
   - Test caching behavior
   - Test aggregation filters
   - Mock ProductCostRepository responses
   - Test top products ranking

3. **WebhookProcessor**
   - Test signature verification with valid/invalid signatures
   - Test event routing for all event types
   - Test retry logic (max retries, increment logic)
   - Mock OrderSyncService calls
   - Test error handling for each event type

### Integration Tests
1. Full order sync workflow (channel → UnifiedOrder)
2. Webhook processing end-to-end
3. Profitability calculation with real ProductCost data
4. Batch order synchronization with mixed success/failure

### Feature Tests
1. API endpoint for webhook reception
2. Admin dashboard profitability reports
3. Order sync command (Artisan)

---

## Usage Examples

### OrderSyncService
```php
$orderSyncService = app(OrderSyncService::class);
$channel = Channel::find(1);
$orderData = [
    'channel_order_id' => 'SHOP-12345',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'status' => 'open',
    'total_amount' => 150.00,
    'currency_code' => 'USD',
];

$order = $orderSyncService->syncOrderFromChannel($channel, $orderData);
```

### ProfitabilityCalculator
```php
$calculator = app(ProfitabilityCalculator::class);
$order = UnifiedOrder::find(1);

$result = $calculator->calculateOrderProfitability($order);
echo $result->getSummary(); // "Profitable: $150.00 revenue, $90.00 cost, $60.00 profit (40.00% margin)"
```

### WebhookProcessor
```php
$processor = app(WebhookProcessor::class);
$webhook = OrderWebhook::find(1);
$payload = [
    'channel_id' => 1,
    'order' => [
        'channel_order_id' => 'SHOP-12345',
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'status' => 'open',
        'total_amount' => 150.00,
        'currency_code' => 'USD',
    ],
];

$processor->processWebhookEvent($webhook, $payload);
```

---

## File Sizes
- OrderSyncService.php: 13KB (342 lines)
- ProfitabilityCalculator.php: 14KB (392 lines)
- WebhookProcessor.php: 13KB (339 lines)
- ProfitabilityResult.php: 6.2KB (204 lines)
- SyncResult.php: 6.4KB (217 lines)
- WebhookEventPayload.php: 8.4KB (269 lines)

**Total**: 61KB, 1,763 lines of well-documented PHP code

---

## Next Steps
1. Register services in OrderServiceProvider
2. Create unit tests for all services and value objects
3. Create integration tests for workflows
4. Add API controllers for webhook endpoints
5. Create admin UI for profitability reports
6. Add Artisan commands for batch sync
7. Document API endpoints in OpenAPI/Swagger
