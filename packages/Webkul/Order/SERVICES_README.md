# Order Package Services and ValueObjects

## Overview
This document describes the comprehensive Services and ValueObjects created for the Order package to support unified multi-channel order management, profitability analysis, and webhook processing.

## Services (3 files)

### 1. OrderSyncService
**Location**: `src/Services/OrderSyncService.php`

**Purpose**: Handles synchronization of orders from external channels (Salla, Shopify, WooCommerce) into the UnoPim unified order system.

**Key Methods**:
- `syncChannel(int $channelId, array $options): OrderSyncResult` - Sync orders from a specific channel
- `syncMultipleChannels(array $channelIds, array $options): array` - Batch sync multiple channels
- `getSyncStatistics(int $channelId, array $dateRange): array` - Get sync statistics for a channel

**Features**:
- Automatic channel adapter selection based on channel type
- Status and payment status mapping from channel-specific values to unified values
- Comprehensive error handling and logging
- Sync log tracking for audit purposes
- Event dispatching (OrderSynced, SyncFailed)

**Status Mapping**:
- Maps channel-specific statuses (pending, processing, shipped, etc.) to unified statuses
- Maps payment statuses (paid, pending, refunded, etc.) to unified payment statuses

### 2. ProfitabilityCalculator
**Location**: `src/Services/ProfitabilityCalculator.php`

**Purpose**: Calculates profit margins, revenue, and cost basis for orders and order items. Integrates with ProductCost from the Pricing package for accurate cost tracking.

**Key Methods**:
- `calculateOrderProfitability(int $orderId): ProfitabilityResult` - Calculate profitability for a single order
- `aggregateByChannel(int $channelId, array $dateRange): ChannelProfitability` - Aggregate profitability by channel
- `calculateBatchProfitability(array $orderIds): array` - Calculate profitability for multiple orders
- `getTopProfitableProducts(array $filters): array` - Get top profitable products from orders
- `getOverallSummary(array $filters): array` - Calculate overall profitability summary

**Features**:
- Item-level profitability breakdown
- Historical cost tracking (uses effective_from/effective_to from ProductCost)
- Margin percentage calculations
- ROI analysis
- Channel-level aggregation
- Product profitability ranking

### 3. WebhookProcessor
**Location**: `src/Services/WebhookProcessor.php`

**Purpose**: Processes incoming webhook events from external channels. Verifies HMAC signatures, dispatches events, and triggers appropriate sync operations.

**Key Methods**:
- `process(string $channelCode, array $payload, array $headers): WebhookProcessResult` - Process incoming webhook event

**Supported Event Types**:
- `order.created` / `orders/create` - New order created
- `order.updated` / `orders/updated` - Order updated
- `order.cancelled` / `orders/cancelled` - Order cancelled
- `order.fulfilled` / `orders/fulfilled` - Order fulfilled
- `order.paid` / `orders/paid` - Order payment status updated

**Features**:
- HMAC signature verification (SHA256)
- Multi-channel webhook header support (Salla, Shopify, WooCommerce)
- Event normalization across different channel formats
- Automatic order data synchronization
- Event dispatching (WebhookReceived)
- Comprehensive logging

## ValueObjects (6 files)

All ValueObjects are readonly classes (PHP 8.2+) with comprehensive helper methods and serialization support.

### 1. OrderSyncResult
**Location**: `src/ValueObjects/OrderSyncResult.php`

**Purpose**: Represents the result of an order synchronization operation.

**Properties**:
- `bool $success` - Whether the sync operation was successful
- `int $syncedCount` - Number of orders successfully synced
- `int $failedCount` - Number of orders that failed to sync
- `int $totalProcessed` - Total number of orders processed
- `array $errors` - Array of error details
- `?int $syncLogId` - ID of the sync log entry
- `Carbon $startedAt` - When the sync started
- `Carbon $completedAt` - When the sync completed

**Helper Methods**:
- `getDuration(): int` - Get duration in seconds
- `getSuccessRate(): float` - Get success rate as percentage
- `hasErrors(): bool` - Check if there were any errors
- `toArray(): array` - Convert to array representation
- `toJson(): string` - Convert to JSON representation

### 2. ProfitabilityResult
**Location**: `src/ValueObjects/ProfitabilityResult.php`

**Purpose**: Represents profitability analysis for a single order.

**Properties**:
- `int $orderId` - Order ID
- `string $orderNumber` - Order number
- `float $revenue` - Total revenue
- `float $totalCost` - Total cost
- `float $profit` - Net profit
- `float $marginPercentage` - Profit margin percentage
- `array $itemBreakdown` - Array of ItemProfitability objects
- `string $currencyCode` - Currency code
- `Carbon $orderDate` - Order date
- `int $channelId` - Channel ID

**Helper Methods**:
- `isProfitable(): bool` - Check if the order is profitable
- `getItemCount(): int` - Get number of items in the order
- `getAverageProfitPerItem(): float` - Get average profit per item
- `getCostToRevenueRatio(): float` - Get cost to revenue ratio
- `formatCurrency(float $amount): string` - Get formatted currency value

### 3. ItemProfitability
**Location**: `src/ValueObjects/ItemProfitability.php`

**Purpose**: Represents profitability analysis for a single order item.

**Properties**:
- `int $productId` - Product ID
- `string $productName` - Product name
- `string $productSku` - Product SKU
- `int $quantity` - Quantity sold
- `float $unitPrice` - Unit price
- `float $revenue` - Total revenue
- `float $unitCost` - Unit cost
- `float $costBasis` - Total cost
- `float $profit` - Net profit
- `float $marginPercentage` - Profit margin percentage

**Helper Methods**:
- `isProfitable(): bool` - Check if this item is profitable
- `getProfitPerUnit(): float` - Get profit per unit
- `getMarkupPercentage(): float` - Get markup percentage

### 4. ChannelProfitability
**Location**: `src/ValueObjects/ChannelProfitability.php`

**Purpose**: Represents aggregated profitability analysis for a channel.

**Properties**:
- `int $channelId` - Channel ID
- `int $orderCount` - Number of orders
- `float $totalRevenue` - Total revenue
- `float $totalCost` - Total cost
- `float $totalProfit` - Total profit
- `float $averageOrderValue` - Average order value
- `float $profitMargin` - Overall profit margin percentage
- `array $dateRange` - Date range for analysis
- `array $orderBreakdown` - Array of ProfitabilityResult objects

**Helper Methods**:
- `isProfitable(): bool` - Check if the channel is profitable
- `getAverageProfitPerOrder(): float` - Get average profit per order
- `getAverageCostPerOrder(): float` - Get average cost per order
- `getROI(): float` - Get return on investment (ROI) percentage
- `getProfitableOrderCount(): int` - Get number of profitable orders
- `getProfitabilityRate(): float` - Get profitability rate
- `getSummary(): array` - Get summary statistics

### 5. WebhookProcessResult
**Location**: `src/ValueObjects/WebhookProcessResult.php`

**Purpose**: Represents the result of a webhook processing operation.

**Properties**:
- `bool $success` - Whether the webhook was processed successfully
- `string $eventType` - Type of webhook event
- `int $processedOrders` - Number of orders processed
- `string $message` - Result message
- `int $webhookId` - Webhook configuration ID
- `string $channelCode` - Channel code
- `Carbon $processedAt` - When the webhook was processed

**Helper Methods**:
- `hasProcessedOrders(): bool` - Check if any orders were processed
- `isCreationEvent(): bool` - Check if this is a creation event
- `isUpdateEvent(): bool` - Check if this is an update event
- `isCancellationEvent(): bool` - Check if this is a cancellation event
- `getEventCategory(): string` - Get event category

### 6. SyncStatistics
**Location**: `src/ValueObjects/SyncStatistics.php`

**Purpose**: Represents aggregated statistics for order synchronization operations.

**Properties**:
- `int $channelId` - Channel ID
- `int $totalSyncs` - Total number of sync operations
- `int $successfulSyncs` - Number of successful syncs
- `int $failedSyncs` - Number of failed syncs
- `int $totalOrdersSynced` - Total orders synced
- `int $totalOrdersFailed` - Total orders failed
- `float $averageSyncDuration` - Average sync duration in seconds
- `?Carbon $lastSyncAt` - Last sync timestamp
- `?Carbon $firstSyncAt` - First sync timestamp
- `array $dateRange` - Date range for statistics

**Helper Methods**:
- `getSyncSuccessRate(): float` - Get sync success rate as percentage
- `getOrderSuccessRate(): float` - Get order success rate as percentage
- `getAverageOrdersPerSync(): float` - Get average orders per sync
- `getAverageFailedOrdersPerSync(): float` - Get average failed orders per sync
- `getHoursSinceLastSync(): ?float` - Get time since last sync in hours
- `isHealthy(): bool` - Check if sync is healthy (success rate >= 95%)
- `needsAttention(): bool` - Check if sync needs attention (success rate < 80%)
- `getHealthStatus(): string` - Get health status (healthy/warning/critical)
- `getSummary(): array` - Get summary for dashboard display

## Design Patterns

### ValueObjects
- All ValueObjects are **readonly classes** (PHP 8.2+)
- Immutable after construction
- Comprehensive helper methods for common calculations
- `toArray()` and `toJson()` methods for serialization
- Type-safe with full type hints

### Services
- Dependency injection via constructor
- Repository pattern for data access
- Event dispatching for important operations
- Comprehensive error handling and logging
- Channel adapter pattern for multi-channel support

## Integration with Other Packages

### Dependencies
- **Webkul\Channel**: Channel models and configuration
- **Webkul\Pricing**: ProductCost models for cost tracking
- **Webkul\Order\Models**: UnifiedOrder, UnifiedOrderItem, OrderWebhook
- **Webkul\Order\Contracts**: Repository contracts
- **Webkul\Order\Events**: Event classes

### Usage Examples

#### Order Synchronization
```php
use Webkul\Order\Services\OrderSyncService;

$syncService = app(OrderSyncService::class);

// Sync single channel
$result = $syncService->syncChannel(
    channelId: 1,
    options: [
        'date_from' => '2024-01-01',
        'status_filter' => 'pending'
    ]
);

echo "Synced: {$result->syncedCount}, Failed: {$result->failedCount}";
echo "Success Rate: {$result->getSuccessRate()}%";
```

#### Profitability Analysis
```php
use Webkul\Order\Services\ProfitabilityCalculator;

$calculator = app(ProfitabilityCalculator::class);

// Calculate order profitability
$result = $calculator->calculateOrderProfitability(orderId: 123);

if ($result->isProfitable()) {
    echo "Profit: {$result->formatCurrency($result->profit)}";
    echo "Margin: {$result->marginPercentage}%";
}

// Get channel profitability
$channelProfit = $calculator->aggregateByChannel(
    channelId: 1,
    dateRange: [
        'start' => now()->subMonth(),
        'end' => now()
    ]
);

echo "Channel ROI: {$channelProfit->getROI()}%";
```

#### Webhook Processing
```php
use Webkul\Order\Services\WebhookProcessor;

$processor = app(WebhookProcessor::class);

// Process webhook
$result = $processor->process(
    channelCode: 'salla',
    payload: $request->all(),
    headers: $request->headers->all()
);

if ($result->success) {
    echo "Processed {$result->processedOrders} orders";
    echo "Event: {$result->getEventCategory()}";
}
```

## Testing

### Unit Tests
All services should have comprehensive unit tests covering:
- Happy path scenarios
- Error handling
- Edge cases
- Channel-specific logic
- ValueObject calculations

### Integration Tests
Test integration with:
- Channel adapters
- Repository layer
- Event system
- External APIs (via mocks)

## Future Enhancements

1. **Async Processing**: Queue-based webhook processing for high-volume scenarios
2. **Caching**: Cache profitability calculations for frequently accessed orders
3. **Batch Operations**: Optimize batch sync operations with database transactions
4. **Analytics**: Advanced analytics dashboard using aggregated statistics
5. **Alerting**: Automatic alerts when sync health degrades

---

**Created**: 2024-02-17
**Package**: Webkul/Order
**UnoPim Version**: 1.0
