<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Package Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Order package.
    |
    */

    // ACL Permission Labels
    'acl' => [
        'order' => 'Orders',
        'orders' => 'Order Management',
        'orders.view' => 'View Orders',
        'orders.create' => 'Create Orders',
        'orders.edit' => 'Edit Orders',
        'orders.delete' => 'Delete Orders',
        'sync' => 'Order Synchronization',
        'sync.view' => 'View Sync Logs',
        'sync.trigger' => 'Trigger Sync',
        'sync.retry' => 'Retry Failed Sync',
        'sync.configure' => 'Configure Sync Settings',
        'profitability' => 'Profitability Analysis',
        'profitability.view' => 'View Profitability',
        'profitability.calculate' => 'Calculate Profitability',
        'profitability.export' => 'Export Reports',
        'webhooks' => 'Order Webhooks',
        'webhooks.view' => 'View Webhooks',
        'webhooks.create' => 'Create Webhooks',
        'webhooks.edit' => 'Edit Webhooks',
        'webhooks.delete' => 'Delete Webhooks',
        'webhooks.test' => 'Test Webhooks',
        'settings' => 'Order Settings',
        'settings.view' => 'View Settings',
        'settings.edit' => 'Edit Settings',
        'items' => 'Order Items',
        'items.view' => 'View Order Items',
        'items.edit' => 'Edit Order Items',
        'history' => 'Order History',
        'history.view' => 'View Order History',
    ],

    // Menu Labels
    'menu' => [
        'orders' => 'Orders',
        'order-management' => 'Order Management',
        'order-sync' => 'Order Sync',
        'profitability-analysis' => 'Profitability Analysis',
        'order-webhooks' => 'Order Webhooks',
        'order-settings' => 'Order Settings',
    ],

    // Orders Index Page
    'orders' => [
        'index' => [
            'title' => 'Orders',
            'create-btn' => 'Create Order',
            'export-btn' => 'Export Orders',
            'sync-btn' => 'Sync Orders',
            'filter-btn' => 'Filter',
            'reset-btn' => 'Reset',
            'search-placeholder' => 'Search orders...',
            'no-orders' => 'No orders found',
            'total-orders' => 'Total Orders',
            'today-orders' => 'Today\'s Orders',
            'pending-orders' => 'Pending Orders',
            'revenue-today' => 'Today\'s Revenue',
        ],

        // Create/Edit Order
        'create' => [
            'title' => 'Create Order',
            'success' => 'Order created successfully',
            'error' => 'Failed to create order',
        ],

        'edit' => [
            'title' => 'Edit Order',
            'success' => 'Order updated successfully',
            'error' => 'Failed to update order',
            'back-btn' => 'Back to Orders',
            'save-btn' => 'Save Order',
        ],

        'delete' => [
            'title' => 'Delete Order',
            'confirm' => 'Are you sure you want to delete this order?',
            'success' => 'Order deleted successfully',
            'error' => 'Failed to delete order',
        ],

        // View Order Details
        'view' => [
            'title' => 'Order Details',
            'order-info' => 'Order Information',
            'customer-info' => 'Customer Information',
            'payment-info' => 'Payment Information',
            'shipping-info' => 'Shipping Information',
            'items-info' => 'Order Items',
            'history-info' => 'Order History',
            'profitability-info' => 'Profitability Analysis',
            'print-btn' => 'Print Order',
            'invoice-btn' => 'Generate Invoice',
        ],

        // Order Fields
        'fields' => [
            'order-number' => 'Order Number',
            'channel' => 'Channel',
            'channel-order-id' => 'Channel Order ID',
            'customer-name' => 'Customer Name',
            'customer-email' => 'Customer Email',
            'customer-phone' => 'Customer Phone',
            'status' => 'Order Status',
            'payment-status' => 'Payment Status',
            'payment-method' => 'Payment Method',
            'shipping-method' => 'Shipping Method',
            'total-amount' => 'Total Amount',
            'subtotal' => 'Subtotal',
            'tax-amount' => 'Tax Amount',
            'shipping-amount' => 'Shipping Amount',
            'discount-amount' => 'Discount Amount',
            'order-date' => 'Order Date',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
            'items-count' => 'Items Count',
            'profit' => 'Profit',
            'margin-percentage' => 'Margin %',
            'cost-price' => 'Cost Price',
            'selling-price' => 'Selling Price',
            'currency' => 'Currency',
            'notes' => 'Notes',
            'internal-notes' => 'Internal Notes',
            'shipping-address' => 'Shipping Address',
            'billing-address' => 'Billing Address',
            'tracking-number' => 'Tracking Number',
            'carrier' => 'Carrier',
        ],

        // Order Items
        'items' => [
            'title' => 'Order Items',
            'sku' => 'SKU',
            'product-name' => 'Product Name',
            'quantity' => 'Quantity',
            'unit-price' => 'Unit Price',
            'total-price' => 'Total Price',
            'discount' => 'Discount',
            'tax' => 'Tax',
            'cost-price' => 'Cost Price',
            'profit' => 'Profit',
            'no-items' => 'No items in this order',
        ],
    ],

    // Order Status
    'status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'on-hold' => 'On Hold',
        'failed' => 'Failed',
    ],

    // Payment Status
    'payment-status' => [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'partially-paid' => 'Partially Paid',
        'refunded' => 'Refunded',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'authorized' => 'Authorized',
        'captured' => 'Captured',
    ],

    // Order Synchronization
    'sync' => [
        'index' => [
            'title' => 'Order Synchronization',
            'sync-now-btn' => 'Sync Now',
            'sync-history' => 'Sync History',
            'last-sync' => 'Last Sync',
            'next-sync' => 'Next Sync',
            'auto-sync-enabled' => 'Auto Sync Enabled',
            'manual-sync' => 'Manual Sync',
        ],

        'create' => [
            'title' => 'Trigger Order Sync',
            'select-channel' => 'Select Channel',
            'select-date-range' => 'Select Date Range',
            'sync-type' => 'Sync Type',
            'full-sync' => 'Full Sync',
            'incremental-sync' => 'Incremental Sync',
            'start-sync-btn' => 'Start Sync',
        ],

        'status' => [
            'queued' => 'Queued',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'partial' => 'Partial Success',
            'cancelled' => 'Cancelled',
        ],

        'fields' => [
            'sync-id' => 'Sync ID',
            'channel' => 'Channel',
            'sync-type' => 'Sync Type',
            'status' => 'Status',
            'started-at' => 'Started At',
            'completed-at' => 'Completed At',
            'total-orders' => 'Total Orders',
            'synced-orders' => 'Synced Orders',
            'failed-orders' => 'Failed Orders',
            'error-message' => 'Error Message',
            'duration' => 'Duration',
        ],

        'actions' => [
            'view-details' => 'View Details',
            'retry' => 'Retry',
            'cancel' => 'Cancel',
            'download-log' => 'Download Log',
        ],

        'messages' => [
            'sync-started' => 'Order sync started successfully for :channel',
            'sync-completed' => 'Order sync completed successfully. :count orders synced.',
            'sync-failed' => 'Order sync failed: :error',
            'sync-cancelled' => 'Order sync cancelled',
            'retry-success' => 'Sync retry initiated successfully',
            'no-orders-to-sync' => 'No orders to sync for the selected criteria',
        ],
    ],

    // Profitability Analysis
    'profitability' => [
        'index' => [
            'title' => 'Profitability Analysis',
            'calculate-btn' => 'Calculate Profitability',
            'export-btn' => 'Export Report',
            'date-range' => 'Date Range',
            'filter-by-channel' => 'Filter by Channel',
            'filter-by-status' => 'Filter by Status',
        ],

        'summary' => [
            'title' => 'Profitability Summary',
            'total-revenue' => 'Total Revenue',
            'total-cost' => 'Total Cost',
            'total-profit' => 'Total Profit',
            'average-margin' => 'Average Margin',
            'profit-by-channel' => 'Profit by Channel',
            'profit-by-product' => 'Profit by Product',
            'profit-trend' => 'Profit Trend',
        ],

        'fields' => [
            'order-number' => 'Order Number',
            'revenue' => 'Revenue',
            'cost' => 'Cost',
            'profit' => 'Profit',
            'margin' => 'Margin %',
            'channel' => 'Channel',
            'product' => 'Product',
            'category' => 'Category',
            'calculated-at' => 'Calculated At',
        ],

        'messages' => [
            'calculation-started' => 'Profitability calculation started',
            'calculation-completed' => 'Profitability calculated successfully for :count orders',
            'calculation-failed' => 'Profitability calculation failed: :error',
            'export-success' => 'Report exported successfully',
            'no-data' => 'No profitability data available for the selected criteria',
        ],
    ],

    // Order Webhooks
    'webhooks' => [
        'index' => [
            'title' => 'Order Webhooks',
            'create-btn' => 'Create Webhook',
            'test-btn' => 'Test Webhook',
            'active-webhooks' => 'Active Webhooks',
            'inactive-webhooks' => 'Inactive Webhooks',
        ],

        'create' => [
            'title' => 'Create Webhook',
            'success' => 'Webhook created successfully',
            'error' => 'Failed to create webhook',
        ],

        'edit' => [
            'title' => 'Edit Webhook',
            'success' => 'Webhook updated successfully',
            'error' => 'Failed to update webhook',
        ],

        'delete' => [
            'confirm' => 'Are you sure you want to delete this webhook?',
            'success' => 'Webhook deleted successfully',
            'error' => 'Failed to delete webhook',
        ],

        'fields' => [
            'name' => 'Webhook Name',
            'url' => 'Webhook URL',
            'event' => 'Event',
            'channels' => 'Channels',
            'active' => 'Active',
            'secret' => 'Secret Key',
            'headers' => 'Custom Headers',
            'retry-count' => 'Retry Count',
            'timeout' => 'Timeout (seconds)',
            'last-triggered' => 'Last Triggered',
            'success-count' => 'Success Count',
            'failure-count' => 'Failure Count',
        ],

        'events' => [
            'order-created' => 'Order Created',
            'order-updated' => 'Order Updated',
            'order-cancelled' => 'Order Cancelled',
            'order-completed' => 'Order Completed',
            'order-refunded' => 'Order Refunded',
            'payment-received' => 'Payment Received',
            'order-shipped' => 'Order Shipped',
            'order-delivered' => 'Order Delivered',
        ],

        'messages' => [
            'test-success' => 'Webhook test successful',
            'test-failed' => 'Webhook test failed: :error',
            'triggered' => 'Webhook triggered successfully',
            'trigger-failed' => 'Webhook trigger failed: :error',
        ],
    ],

    // Order Settings
    'settings' => [
        'index' => [
            'title' => 'Order Settings',
            'general-settings' => 'General Settings',
            'sync-settings' => 'Sync Settings',
            'notification-settings' => 'Notification Settings',
            'save-btn' => 'Save Settings',
        ],

        'general' => [
            'auto-approve-orders' => 'Auto Approve Orders',
            'order-number-prefix' => 'Order Number Prefix',
            'default-currency' => 'Default Currency',
            'allow-guest-orders' => 'Allow Guest Orders',
            'minimum-order-amount' => 'Minimum Order Amount',
            'maximum-order-amount' => 'Maximum Order Amount',
        ],

        'sync' => [
            'enable-auto-sync' => 'Enable Auto Sync',
            'sync-interval' => 'Sync Interval (minutes)',
            'sync-channels' => 'Channels to Sync',
            'sync-order-status' => 'Sync Order Status Updates',
            'sync-inventory' => 'Sync Inventory Updates',
            'retry-failed-sync' => 'Auto Retry Failed Sync',
            'max-retry-attempts' => 'Max Retry Attempts',
        ],

        'notifications' => [
            'notify-on-new-order' => 'Notify on New Order',
            'notify-on-status-change' => 'Notify on Status Change',
            'notify-on-sync-failure' => 'Notify on Sync Failure',
            'notification-email' => 'Notification Email',
            'notification-channels' => 'Notification Channels',
        ],

        'messages' => [
            'save-success' => 'Settings saved successfully',
            'save-error' => 'Failed to save settings',
            'reset-success' => 'Settings reset to defaults',
        ],
    ],

    // Validation Messages
    'validation' => [
        'order-number-required' => 'Order number is required',
        'order-number-unique' => 'Order number must be unique',
        'channel-required' => 'Channel is required',
        'customer-name-required' => 'Customer name is required',
        'customer-email-required' => 'Customer email is required',
        'customer-email-valid' => 'Customer email must be valid',
        'status-required' => 'Order status is required',
        'status-invalid' => 'Invalid order status',
        'total-amount-required' => 'Total amount is required',
        'total-amount-positive' => 'Total amount must be positive',
        'items-required' => 'Order must have at least one item',
        'webhook-url-required' => 'Webhook URL is required',
        'webhook-url-valid' => 'Webhook URL must be valid',
        'webhook-event-required' => 'Webhook event is required',
    ],

    // General Messages
    'messages' => [
        'create-success' => 'Order created successfully',
        'update-success' => 'Order updated successfully',
        'delete-success' => 'Order deleted successfully',
        'status-updated' => 'Order status updated to :status',
        'payment-status-updated' => 'Payment status updated to :status',
        'sync-success' => 'Orders synced successfully from :channel',
        'sync-failed' => 'Order sync failed: :error',
        'webhook-created' => 'Webhook created successfully',
        'profitability-calculated' => 'Profitability calculated successfully',
        'export-success' => 'Orders exported successfully',
        'import-success' => ':count orders imported successfully',
        'no-permission' => 'You do not have permission to perform this action',
        'order-not-found' => 'Order not found',
        'invalid-status-transition' => 'Cannot transition from :from to :to',
    ],

    // DataGrid Column Headers
    'datagrid' => [
        'id' => 'ID',
        'order-number' => 'Order Number',
        'channel' => 'Channel',
        'customer' => 'Customer',
        'status' => 'Status',
        'payment-status' => 'Payment Status',
        'total' => 'Total',
        'items' => 'Items',
        'date' => 'Date',
        'actions' => 'Actions',
        'profit' => 'Profit',
        'margin' => 'Margin',
    ],

    // Filter Labels
    'filters' => [
        'all-orders' => 'All Orders',
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'date-range' => 'Date Range',
        'channel' => 'Channel',
        'status' => 'Status',
        'payment-status' => 'Payment Status',
        'customer' => 'Customer',
        'apply' => 'Apply Filters',
        'clear' => 'Clear Filters',
    ],

    // Action Labels
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'sync' => 'Sync',
        'export' => 'Export',
        'print' => 'Print',
        'invoice' => 'Invoice',
        'cancel' => 'Cancel Order',
        'refund' => 'Refund',
        'ship' => 'Mark as Shipped',
        'deliver' => 'Mark as Delivered',
        'complete' => 'Mark as Completed',
    ],

    // Tooltips and Help Text
    'tooltips' => [
        'order-number' => 'Unique identifier for this order',
        'channel-order-id' => 'Original order ID from the sales channel',
        'auto-sync' => 'Automatically sync orders at specified intervals',
        'webhook-secret' => 'Used to verify webhook authenticity',
        'retry-count' => 'Number of times to retry failed webhook deliveries',
        'profit-margin' => 'Calculated as (Selling Price - Cost Price) / Selling Price × 100',
    ],
];
