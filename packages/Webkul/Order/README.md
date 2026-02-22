# Order Package

Unified order management system for ECOM-OS platform.

## Features

- **Unified Order Management**: Centralized order handling from multiple channels
- **Order Synchronization**: Automatic sync from external channels (Shopify, Salla, etc.)
- **Profitability Tracking**: Real-time profitability calculations and reporting
- **Webhook Support**: Receive and process order webhooks from external platforms

## Structure

```
src/
├── Config/           # ACL and menu configurations
├── Database/         # Migrations
├── Listeners/        # Event listeners
├── Models/          # Eloquent models
├── Providers/       # Service providers
├── Resources/       # Views and translations
├── Routes/          # Admin and API routes
└── Services/        # Business logic services
```

## Models

- **UnifiedOrder**: Main order entity
- **UnifiedOrderItem**: Order line items
- **OrderSyncLog**: Synchronization tracking
- **OrderWebhook**: Webhook configurations

## Services

- **OrderSyncService**: Handles order synchronization from external channels
- **ProfitabilityCalculator**: Calculates order profitability and margins
- **WebhookProcessor**: Processes incoming webhooks

## Events

- `order.received` - Fired when new order is received
- `order.status.changed` - Fired when order status changes
- `order.profitability.calculated` - Fired after profitability calculation

## Installation

This package is part of the UnoPim modular system and is automatically registered via Concord.

## License

OSL-3.0
