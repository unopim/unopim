# Webkul/Order Package Structure

Complete scaffold for the unified order management system.

## Package Information
- **Name**: webkul/order
- **Namespace**: Webkul\Order
- **License**: OSL-3.0
- **PHP Requirement**: >=8.2

## Created Files

### Core Configuration
- `composer.json` - Package definition with PSR-4 autoloading
- `README.md` - Package documentation

### Service Providers (src/Providers/)
- `OrderServiceProvider.php` - Main service provider
  - Loads migrations from ../Database/Migrations
  - Loads routes from ../Routes/admin-routes.php and ../Routes/api-routes.php
  - Loads views from ../Resources/views with namespace 'order'
  - Loads translations from ../Resources/lang with namespace 'order'
  - Registers EventServiceProvider and ModuleServiceProvider
  - Merges configs: menu.php (menu.admin), acl.php (acl)
  - Registers singleton services: OrderSyncService, ProfitabilityCalculator, WebhookProcessor

- `ModuleServiceProvider.php` - Concord module provider
  - Extends CoreModuleServiceProvider
  - Registers models: UnifiedOrder, UnifiedOrderItem, OrderSyncLog, OrderWebhook

- `EventServiceProvider.php` - Event/listener mappings
  - order.received => [SyncOrderToUnified, CalculateProfitability]
  - order.status.changed => [NotifyChannelUpdates, UpdateMetrics]
  - order.profitability.calculated => [UpdateOrderCache]

### Configuration (src/Config/)
- `acl.php` - 28 ACL permissions organized in 4 groups:
  - order (parent, sort: 13)
  - order.orders (view, create, edit, delete)
  - order.sync (view, trigger, retry)
  - order.profitability (view, analyze)
  - order.webhooks (view, create, edit, delete)

- `menu.php` - 7 menu entries:
  - order (parent, icon: icon-orders, sort: 9)
  - order.orders
  - order.sync
  - order.profitability
  - order.webhooks

### Models (src/Models/)
- `UnifiedOrder.php` - Main order entity
  - Uses HistoryTrait
  - Table: unified_orders
  - Mass assignable: channel_id, channel_type, channel_order_id, customer_name, customer_email, status, total_amount, currency_code, order_data
  - Casts: order_data (array), total_amount (decimal:2)
  - Relationships: hasMany items

- `UnifiedOrderItem.php` - Order line items
  - Table: unified_order_items
  - Mass assignable: order_id, product_id, sku, name, quantity, price, total, item_data
  - Casts: item_data (array), price (decimal:2), total (decimal:2)
  - Relationships: belongsTo order

- `OrderSyncLog.php` - Synchronization tracking
  - Table: order_sync_logs
  - Mass assignable: order_id, channel_type, channel_order_id, status, error_message, sync_data, synced_at
  - Casts: sync_data (array), synced_at (datetime)

- `OrderWebhook.php` - Webhook configurations
  - Table: order_webhooks
  - Mass assignable: channel_id, channel_type, event_type, webhook_url, secret_key, is_active
  - Casts: is_active (boolean)

### Services (src/Services/)
- `OrderSyncService.php` - Order synchronization service
  - syncOrder(array $orderData, string $channelType)
  - retrySync(int $syncLogId): bool

- `ProfitabilityCalculator.php` - Profitability calculation service
  - calculate(int $orderId): array
  - generateReport(array $filters = []): array

- `WebhookProcessor.php` - Webhook processing service
  - process(string $channelType, array $payload): bool
  - validateSignature(string $channelType, array $headers, string $payload): bool

### Event Listeners (src/Listeners/)
- `SyncOrderToUnified.php` - Handles order.received event
- `CalculateProfitability.php` - Handles order.received event
- `NotifyChannelUpdates.php` - Handles order.status.changed event
- `UpdateMetrics.php` - Handles order.status.changed event
- `UpdateOrderCache.php` - Handles order.profitability.calculated event

### Routes (src/Routes/)
- `admin-routes.php` - Admin panel routes
  - Middleware: web, admin_locale
  - Prefix: config('app.admin_path')
  - Routes: /order/orders, /order/sync, /order/profitability, /order/webhooks

- `api-routes.php` - API routes
  - Middleware: api
  - Prefix: api/v1
  - Routes: POST /orders/webhooks/{channel}

### Translations (src/Resources/lang/en/)
- `app.php` - English translations
  - menu translations (5 entries)
  - acl translations (13 entries)
  - UI translations (4 sections)

### Directory Structure
```
packages/Webkul/Order/
├── composer.json
├── README.md
├── PACKAGE_STRUCTURE.md (this file)
├── src/
│   ├── Config/
│   │   ├── acl.php
│   │   └── menu.php
│   ├── Database/
│   │   └── Migrations/ (empty, for future migrations)
│   ├── Listeners/
│   │   ├── CalculateProfitability.php
│   │   ├── NotifyChannelUpdates.php
│   │   ├── SyncOrderToUnified.php
│   │   ├── UpdateMetrics.php
│   │   └── UpdateOrderCache.php
│   ├── Models/
│   │   ├── OrderSyncLog.php
│   │   ├── OrderWebhook.php
│   │   ├── UnifiedOrder.php
│   │   └── UnifiedOrderItem.php
│   ├── Providers/
│   │   ├── EventServiceProvider.php
│   │   ├── ModuleServiceProvider.php
│   │   └── OrderServiceProvider.php
│   ├── Resources/
│   │   ├── lang/
│   │   │   └── en/
│   │   │       └── app.php
│   │   └── views/ (empty, for future views)
│   ├── Routes/
│   │   ├── admin-routes.php
│   │   └── api-routes.php
│   └── Services/
│       ├── OrderSyncService.php
│       ├── ProfitabilityCalculator.php
│       └── WebhookProcessor.php
└── tests/ (empty, for future tests)
```

## Integration with UnoPim

The package follows UnoPim's exact patterns:

1. **Konekt Concord Integration**: ModuleServiceProvider extends CoreModuleServiceProvider
2. **Service Provider Registration**: Registered in composer.json extra.laravel.providers
3. **ACL System**: Permissions registered via acl.php merged into global ACL tree
4. **Menu System**: Menu items registered via menu.php merged into admin menu
5. **Event System**: Laravel event/listener pattern with EventServiceProvider
6. **History Tracking**: UnifiedOrder uses HistoryTrait for version control
7. **Translation System**: Resources/lang with 'order' namespace
8. **View System**: Resources/views with 'order' namespace
9. **Route Organization**: Separate admin-routes.php and api-routes.php files

## Next Steps

1. Create database migrations for the 4 tables
2. Create Contracts (interfaces) for models
3. Create Proxy classes for models
4. Create Repository classes for data access
5. Implement service methods
6. Create controller classes
7. Create Blade views
8. Create Vue.js components
9. Write tests
10. Register package in config/concord.php

## Notes

All service methods contain placeholder implementations with comments:
"// Implementation will be added in future stories"

This allows the package structure to be complete and loadable without causing errors,
while leaving actual business logic for subsequent development stories.

