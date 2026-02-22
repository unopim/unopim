# Order Management Package - Manual Test Cases

## Document Information

| Field        | Value                                          |
| ------------ | ---------------------------------------------- |
| Module       | Order Management                               |
| Feature      | Unified Multi-Channel Order Management         |
| Date         | 2026-02-18                                     |
| Version      | 1.0                                            |
| Package      | Webkul/Order                                   |
| Frontend URL | `http://localhost:8000/admin/orders`           |
| API Base     | `/api/v1/order`                                |
| Collections  | `unified_orders`, `unified_order_items`, `order_sync_logs`, `order_webhooks` |

---

## Test Credentials

| Role           | Username          | Email                            | Password    | Tenant      |
| -------------- | ----------------- | -------------------------------- | ----------- | ----------- |
| **Admin**      | `admin`           | `admin@unopim.local`             | `Admin@123` | `default`   |
| **Order Mgr**  | `order.manager`   | `order.manager@unopim.local`     | `Admin@123` | `default`   |
| **Warehouse**  | `warehouse.staff` | `warehouse.staff@unopim.local`   | `Admin@123` | `default`   |
| **Read Only**  | `viewer`          | `viewer@unopim.local`            | `Admin@123` | `default`   |

---

## 1. Unified Order Management

### 1.1 View Orders

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **ORD-VW-001** | List All Orders                         | **Admin**      | Orders exist             | 1. Navigate to **Orders** > **All Orders**.<br>2. View order list.                                                                                                                                                                          | - All orders displayed.<br>- Paginated (20 per page).<br>- Columns: order_number, channel, customer, status, total, date. |
| **ORD-VW-002** | Filter by Channel                       | **Order Mgr**  | Multi-channel orders     | 1. Select filter: Channel = "Salla Store".<br>2. Apply filter.                                                                                                                                                                              | - Only Salla orders shown.<br>- Channel filter applied correctly.                                               |
| **ORD-VW-003** | Filter by Status                        | **Order Mgr**  | Various statuses         | 1. Select filter: Status = "Completed".<br>2. Apply filter.                                                                                                                                                                                 | - Only completed orders.<br>- Status badge color-coded.                                                         |
| **ORD-VW-004** | Filter by Payment Status                | **Order Mgr**  | Various payment statuses | 1. Select filter: Payment Status = "Paid".                                                                                                                                                                                                   | - Only paid orders shown.                                                                                       |
| **ORD-VW-005** | Filter by Date Range                    | **Order Mgr**  | Historical orders        | 1. Set date range: Last 30 days.<br>2. Apply filter.                                                                                                                                                                                         | - Orders within date range.<br>- Date filter works correctly.                                                   |
| **ORD-VW-006** | Search by Order Number                  | **Order Mgr**  | Orders exist             | 1. Enter order number in search.<br>2. Press search.                                                                                                                                                                                         | - Matching order displayed.<br>- Quick search works.                                                            |
| **ORD-VW-007** | Search by Customer Email                | **Order Mgr**  | Orders exist             | 1. Enter customer email in search.<br>2. Press search.                                                                                                                                                                                       | - All orders for that customer.                                                                                 |
| **ORD-VW-008** | View Order Details                      | **Order Mgr**  | Order exists             | 1. Click on order row.<br>2. View detail page.                                                                                                                                                                                               | - Full order information.<br>- Customer details.<br>- Order items table.<br>- Addresses.<br>- Profitability metrics. |
| **ORD-VW-009** | Order Details - Customer Info           | **Order Mgr**  | Order exists             | 1. View order details.<br>2. Check customer section.                                                                                                                                                                                         | - Customer name, email, phone displayed.<br>- Customer card formatted correctly.                                |
| **ORD-VW-010** | Order Details - Shipping Address        | **Order Mgr**  | Order exists             | 1. View order details.<br>2. Check shipping address.                                                                                                                                                                                         | - Full shipping address from JSON.<br>- Formatted display.                                                      |
| **ORD-VW-011** | Order Details - Billing Address         | **Order Mgr**  | Order exists             | 1. View order details.<br>2. Check billing address.                                                                                                                                                                                          | - Full billing address from JSON.<br>- Formatted display.                                                       |
| **ORD-VW-012** | Order Details - Order Items             | **Order Mgr**  | Order with items         | 1. View order details.<br>2. Check items table.                                                                                                                                                                                              | - All items listed.<br>- Columns: product, SKU, qty, price, cost, profit.<br>- Item-level profitability shown. |
| **ORD-VW-013** | Order Details - Profitability Card      | **Order Mgr**  | Order exists             | 1. View order details.<br>2. Check profitability sidebar.                                                                                                                                                                                    | - Total Revenue, Cost, Profit, Margin %.<br>- Color-coded profit indicator (green/red).                         |
| **ORD-VW-014** | Order Timeline                          | **Order Mgr**  | Order with history       | 1. View order details.<br>2. Scroll to timeline section.                                                                                                                                                                                     | - Order creation, status changes, sync events.<br>- Chronological display.                                      |

### 1.2 Edit Orders

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **ORD-ED-001** | Edit Order Status          | **Order Mgr**  | Order active           | 1. Click **"Edit Order"**.<br>2. Change status: Pending → Processing.<br>3. Save.                                                                                                                                                   | - Status updated.<br>- OrderStatusUpdated event dispatched.<br>- Toast: "Order updated successfully".           |
| **ORD-ED-002** | Edit Tracking Number       | **Warehouse**  | Order processing       | 1. Edit order.<br>2. Add tracking number: "TRK123456789".<br>3. Save.                                                                                                                                                               | - Tracking number saved.<br>- Customer notification (if enabled).                                               |
| **ORD-ED-003** | Edit Internal Notes        | **Order Mgr**  | Order exists           | 1. Edit order.<br>2. Add note: "Customer requested express shipping".<br>3. Save.                                                                                                                                                    | - Note saved.<br>- Only visible to internal staff.                                                              |
| **ORD-ED-004** | Cannot Edit Customer Info  | **Order Mgr**  | Order synced           | 1. Try to edit customer_name, customer_email.                                                                                                                                                                                        | - Fields read-only (synced from channel).<br>- Warning message shown.                                           |
| **ORD-ED-005** | Cannot Edit Order Items    | **Order Mgr**  | Order synced           | 1. Try to edit order items.                                                                                                                                                                                                          | - Items read-only (synced from channel).<br>- Contact channel to modify.                                        |
| **ORD-ED-006** | Status Transition Rules    | **Order Mgr**  | Order pending          | 1. Try to set status: Pending → Completed (skip Processing).                                                                                                                                                                        | - **Option A**: Allowed (any status).<br>- **Option B**: Validation error (must go through states).             |
| **ORD-ED-007** | Edit - Unauthorized        | **Read Only**  | Order exists           | 1. Try to edit order.                                                                                                                                                                                                                | - 403 Forbidden.<br>- Permission required: `order.orders.edit`.                                                  |

### 1.3 Mass Update Orders

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **ORD-MA-001** | Mass Update Status         | **Order Mgr**  | Multiple orders        | 1. Select 10 orders.<br>2. Click **"Mass Update"**.<br>3. Set status: Processing.<br>4. Confirm.                                                                                                                                    | - All 10 orders updated.<br>- Batch processing.<br>- Toast: "10 orders updated successfully".                   |
| **ORD-MA-002** | Mass Export                | **Order Mgr**  | Multiple orders        | 1. Select orders or use filters.<br>2. Click **"Export"**.<br>3. Choose format: CSV.                                                                                                                                                | - CSV file downloaded.<br>- All order data included.                                                            |

### 1.4 Delete Orders

| ID             | Title                 | Role           | Pre-conditions     | Detailed Test Steps           | Expected Result                                                                                     |
|----------------|-----------------------|----------------|--------------------|-------------------------------|-----------------------------------------------------------------------------------------------------|
| **ORD-DL-001** | Delete Order - Admin  | **Admin**      | Order exists       | 1. Delete order.<br>2. Confirm. | - Order soft deleted (deleted_at set).<br>- Toast: "Order deleted successfully".                    |
| **ORD-DL-002** | Delete - Unauthorized | **Read Only**  | Order exists       | 1. Try to delete order.        | - 403 Forbidden.                                                                                    |
| **ORD-DL-003** | Cannot Delete Completed | **Order Mgr** | Order completed    | 1. Try to delete completed order. | - **Option A**: Prevented (completed orders cannot be deleted).<br>- **Option B**: Warning shown. |

---

## 2. Order Synchronization

### 2.1 Manual Sync

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **SYNC-MN-001** | Manual Sync Single Channel             | **Order Mgr**  | Channel configured       | 1. Navigate to **Orders** > **Sync** > **Manual Sync**.<br>2. Select Channel: "Salla Store".<br>3. Sync Mode: "Full" / "Incremental".<br>4. Date Range: Last 7 days.<br>5. Click **"Start Sync"**.                                         | - Sync job dispatched.<br>- Sync log created with status "In Progress".<br>- Toast: "Sync started for Salla Store". |
| **SYNC-MN-002** | Manual Sync All Channels               | **Order Mgr**  | Multiple channels        | 1. Click **"Sync All Channels"**.                                                                                                                                                                                                            | - Sync jobs for all active channels.<br>- Multiple sync logs created.                                           |
| **SYNC-MN-003** | Sync with Date Filter                  | **Order Mgr**  | Channel configured       | 1. Set date range: 2026-02-01 to 2026-02-15.<br>2. Start sync.                                                                                                                                                                               | - Only orders within date range synced.<br>- Date filter applied to channel API call.                           |
| **SYNC-MN-004** | Sync Mode - Full                       | **Order Mgr**  | Channel configured       | 1. Select mode: "Full".<br>2. Start sync.                                                                                                                                                                                                    | - All orders re-synced (creates/updates).<br>- Existing orders updated.                                         |
| **SYNC-MN-005** | Sync Mode - Incremental                | **Order Mgr**  | Previous sync exists     | 1. Select mode: "Incremental".<br>2. Start sync.                                                                                                                                                                                             | - Only new/updated orders since last sync.<br>- Uses last_synced_at timestamp.                                  |
| **SYNC-MN-006** | View Sync Progress                     | **Order Mgr**  | Sync in progress         | 1. Navigate to **Sync Logs**.<br>2. View active sync.                                                                                                                                                                                        | - Real-time progress (if implemented).<br>- Records synced counter.<br>- Status: "In Progress".                 |
| **SYNC-MN-007** | Sync Completion                        | **Order Mgr**  | Sync completed           | 1. Wait for sync to complete.<br>2. Check sync log.                                                                                                                                                                                          | - Status: "Completed".<br>- Records synced: X.<br>- Records failed: Y.<br>- Completed_at timestamp set.         |
| **SYNC-MN-008** | Sync Failure Handling                  | **Order Mgr**  | Channel API down         | 1. Start sync when channel offline.<br>2. Check sync log.                                                                                                                                                                                    | - Status: "Failed".<br>- Error details logged.<br>- SyncFailed event dispatched.                                |
| **SYNC-MN-009** | Retry Failed Sync                      | **Order Mgr**  | Sync failed              | 1. View failed sync log.<br>2. Click **"Retry"**.<br>3. Confirm.                                                                                                                                                                             | - New sync job dispatched.<br>- Original sync log updated with retry timestamp.                                 |

### 2.2 Automatic Sync (Scheduled)

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **SYNC-AU-001** | Scheduled Sync Runs        | **System**     | Cron configured        | 1. Wait for scheduled time (e.g., hourly).<br>2. Check sync logs.                                                                                                                                                                       | - Auto-sync runs at scheduled interval.<br>- Sync logs created automatically.                                   |
| **SYNC-AU-002** | Sync Settings              | **Admin**      | Logged in              | 1. Navigate to **Settings** > **Order Sync**.<br>2. Configure:<br>- Sync Frequency: Hourly / Daily<br>- Auto-sync Enabled: Yes<br>3. Save.                                                                                              | - Settings saved.<br>- Cron job updated.                                                                        |

### 2.3 Sync Logs

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **SYNC-LG-001** | View Sync Logs List        | **Order Mgr**  | Sync logs exist        | 1. Navigate to **Orders** > **Sync Logs**.                                                                                                                                                                                               | - All sync logs listed.<br>- Columns: channel, status, records_synced, started_at, completed_at, error_details. |
| **SYNC-LG-002** | Filter Logs by Channel     | **Order Mgr**  | Multiple channels      | 1. Filter: Channel = "Shopify".                                                                                                                                                                                                          | - Only Shopify sync logs.                                                                                       |
| **SYNC-LG-003** | Filter Logs by Status      | **Order Mgr**  | Various statuses       | 1. Filter: Status = "Failed".                                                                                                                                                                                                            | - Only failed sync logs.<br>- Error details visible.                                                            |
| **SYNC-LG-004** | View Sync Log Details      | **Order Mgr**  | Sync log exists        | 1. Click sync log row.<br>2. View details.                                                                                                                                                                                               | - Full sync information.<br>- Statistics breakdown.<br>- Error stack trace (if failed).                         |
| **SYNC-LG-005** | Sync Statistics            | **Order Mgr**  | Sync completed         | 1. View sync log.<br>2. Check statistics.                                                                                                                                                                                                | - Records synced: X.<br>- Records failed: Y.<br>- Duration: Z seconds.<br>- Success rate: %.                    |

---

## 3. Profitability Analysis

### 3.1 Order Profitability

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **PROF-ORD-001** | Calculate Order Profitability         | **Order Mgr**  | Order with items         | 1. View order details.<br>2. Check profitability card.                                                                                                                                                                                       | - Total Revenue: Order total_amount.<br>- Total Cost: Sum of item cost_basis.<br>- Total Profit: Revenue - Cost.<br>- Margin %: (Profit / Revenue) × 100. |
| **PROF-ORD-002** | Item-Level Profitability              | **Order Mgr**  | Order with items         | 1. View order items table.<br>2. Check profit columns.                                                                                                                                                                                       | - Each item shows: price, cost_basis, profit_amount.<br>- Profit = (price - cost_basis) × quantity.            |
| **PROF-ORD-003** | Order with No Costs                   | **Order Mgr**  | Order, items missing costs | 1. View profitability for order with no cost data.                                                                                                                                                                                          | - Cost: 0.00 or N/A.<br>- Profit: Full revenue (unrealistic).<br>- Warning: "Cost data missing".               |
| **PROF-ORD-004** | Negative Profit Order                 | **Order Mgr**  | Cost > Revenue           | 1. View order with high costs.                                                                                                                                                                                                               | - Profit: Negative value in red.<br>- Margin %: Negative.<br>- Alert: "This order is unprofitable".            |
| **PROF-ORD-005** | Multi-Currency Profitability          | **Order Mgr**  | Order in EUR             | 1. View EUR order profitability.                                                                                                                                                                                                             | - All amounts in EUR.<br>- Currency code displayed.                                                             |

### 3.2 Profitability Dashboard

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **PROF-DA-001** | View Dashboard             | **Order Mgr**  | Orders exist           | 1. Navigate to **Orders** > **Profitability**.                                                                                                                                                                                               | - Dashboard with summary cards.<br>- Charts: Revenue vs Profit, Profit by Channel, Top Products.               |
| **PROF-DA-002** | Summary Cards              | **Order Mgr**  | Historical data        | 1. View summary section.                                                                                                                                                                                                                     | - Total Revenue: Sum of all orders.<br>- Total Profit: Sum of all profits.<br>- Avg Margin: Average margin %.<br>- Order Count: Total orders analyzed. |
| **PROF-DA-003** | Revenue vs Profit Chart    | **Order Mgr**  | Time-series data       | 1. View line chart.                                                                                                                                                                                                                          | - X-axis: Time (daily/weekly/monthly).<br>- Y-axis: Revenue (blue), Profit (green).<br>- Chart.js rendering.   |
| **PROF-DA-004** | Profit by Channel Chart    | **Order Mgr**  | Multi-channel orders   | 1. View bar chart.                                                                                                                                                                                                                           | - X-axis: Channels.<br>- Y-axis: Profit.<br>- Color-coded bars.                                                |
| **PROF-DA-005** | Top Profitable Products    | **Order Mgr**  | Product data           | 1. View top products chart.                                                                                                                                                                                                                  | - Horizontal bar chart.<br>- Top 10 products by profit.<br>- Product names and profit amounts.                 |
| **PROF-DA-006** | Filter by Date Range       | **Order Mgr**  | Historical data        | 1. Set date range: Last 30 days.<br>2. Apply filter.                                                                                                                                                                                         | - Dashboard data filtered.<br>- Charts update.<br>- Summary cards recalculate.                                  |
| **PROF-DA-007** | Filter by Channel          | **Order Mgr**  | Multi-channel          | 1. Filter: Channel = "Salla".<br>2. Apply.                                                                                                                                                                                                   | - Only Salla data shown.<br>- All metrics recalculated.                                                         |
| **PROF-DA-008** | Export Profitability Report | **Order Mgr** | Dashboard loaded       | 1. Click **"Export Report"**.<br>2. Choose format: CSV / Excel.                                                                                                                                                                              | - Report file downloaded.<br>- Includes summary and detailed data.                                              |

### 3.3 Channel Profitability

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **PROF-CH-001** | View by Channel            | **Order Mgr**  | Multi-channel orders   | 1. Navigate to **Profitability** > **By Channel**.                                                                                                                                                                                           | - Channel comparison cards.<br>- Table: Channel, Orders, Revenue, Profit, Margin %, Avg Order Value.           |
| **PROF-CH-002** | Channel Comparison Cards   | **Order Mgr**  | Multiple channels      | 1. View channel cards.                                                                                                                                                                                                                       | - Each channel has card with:<br>- Channel name, icon.<br>- Order count.<br>- Revenue, Profit, Margin %.       |
| **PROF-CH-003** | Identify Best Channel      | **Order Mgr**  | Multiple channels      | 1. Compare margin % across channels.                                                                                                                                                                                                         | - Highest margin % highlighted.<br>- Best performer badge.                                                      |
| **PROF-CH-004** | Channel with Negative Profit | **Order Mgr** | Unprofitable channel   | 1. View channel with losses.                                                                                                                                                                                                                 | - Profit in red.<br>- Negative margin %.<br>- Warning indicator.                                                |

### 3.4 Product Profitability

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **PROF-PR-001** | View by Product            | **Order Mgr**  | Product sales exist    | 1. Navigate to **Profitability** > **By Product**.                                                                                                                                                                                           | - Product profitability DataGrid.<br>- Columns: Product, Units Sold, Revenue, Cost, Profit, Margin %.          |
| **PROF-PR-002** | Sort by Profit             | **Order Mgr**  | Multiple products      | 1. Click profit column header.<br>2. Sort descending.                                                                                                                                                                                        | - Products sorted by profit (high to low).<br>- Top profitable products at top.                                |
| **PROF-PR-003** | Filter by Product Category | **Order Mgr**  | Categorized products   | 1. Filter: Category = "Electronics".<br>2. Apply.                                                                                                                                                                                            | - Only electronics shown.<br>- Category-level profitability.                                                    |

---

## 4. Webhook Management

### 4.1 Create Webhook

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **HOOK-CR-001** | Create Basic Webhook                   | **Admin**      | Channel configured       | 1. Navigate to **Orders** > **Webhooks**.<br>2. Click **"Create Webhook"**.<br>3. Name: "Salla Order Webhook".<br>4. Channel: "Salla Store".<br>5. Event Types: order.created, order.updated.<br>6. Active: Yes.<br>7. Click **"Save"**.    | - Webhook created.<br>- Secret key auto-generated.<br>- Endpoint URL displayed.<br>- Toast: "Webhook created successfully". |
| **HOOK-CR-002** | Auto-Generate Secret Key               | **Admin**      | Creating webhook         | 1. Create webhook.<br>2. Check secret_key field.                                                                                                                                                                                             | - Secret key auto-generated (64 chars).<br>- Used for HMAC verification.                                        |
| **HOOK-CR-003** | Display Endpoint URL                   | **Admin**      | Webhook created          | 1. View webhook details.<br>2. Check endpoint URL.                                                                                                                                                                                           | - URL: `/api/v1/order/webhooks/receive/{channel_code}`.<br>- Copyable format.                                   |
| **HOOK-CR-004** | Select Event Types                     | **Admin**      | Creating webhook         | 1. Multi-select event types:<br>- order.created<br>- order.updated<br>- order.cancelled<br>- order.completed<br>- order.paid                                                                                                                 | - All selected events saved.<br>- Webhook triggered only for these events.                                      |
| **HOOK-CR-005** | Create - Missing Name                  | **Admin**      | Creating webhook         | 1. Leave name empty.<br>2. Try to save.                                                                                                                                                                                                      | - Error: "The name field is required".                                                                          |
| **HOOK-CR-006** | Create - Missing Channel               | **Admin**      | Creating webhook         | 1. Leave channel_id empty.<br>2. Try to save.                                                                                                                                                                                                | - Error: "The channel id field is required".                                                                    |
| **HOOK-CR-007** | Create - Invalid Channel Code          | **Admin**      | Creating webhook         | 1. channel_code: "invalid-channel".                                                                                                                                                                                                          | - Error: "Invalid channel code".                                                                                |

### 4.2 Edit Webhook

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **HOOK-ED-001** | Update Webhook Name        | **Admin**      | Webhook exists         | 1. Edit webhook.<br>2. Change name.<br>3. Save.                                                                                                                                                                                              | - Name updated.<br>- Toast: "Webhook updated successfully".                                                     |
| **HOOK-ED-002** | Update Event Types         | **Admin**      | Webhook exists         | 1. Edit webhook.<br>2. Add new event type: order.refunded.<br>3. Save.                                                                                                                                                                       | - Event types updated.<br>- Webhook now triggered for new event.                                                |
| **HOOK-ED-003** | Toggle Webhook Status      | **Admin**      | Webhook active         | 1. Click toggle switch.<br>2. Confirm.                                                                                                                                                                                                       | - is_active toggled.<br>- Disabled webhooks don't process events.                                               |
| **HOOK-ED-004** | Regenerate Secret Key      | **Admin**      | Webhook exists         | 1. Click **"Regenerate Secret"**.<br>2. Warning shown.<br>3. Confirm.                                                                                                                                                                        | - New secret key generated.<br>- Old key invalidated.<br>- Channel must update their HMAC key.                  |

### 4.3 Webhook Processing

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **HOOK-PR-001** | Receive Order Created Event            | **System**     | Webhook configured       | 1. Channel POSTs to webhook endpoint.<br>2. Event: order.created.<br>3. Valid HMAC signature.<br>4. Payload: order data.                                                                                                                     | - Webhook received.<br>- Signature verified.<br>- Order created/updated in database.<br>- WebhookReceived event dispatched. |
| **HOOK-PR-002** | HMAC Signature Verification            | **System**     | Webhook configured       | 1. Channel sends webhook with signature header: X-Webhook-Signature.<br>2. Signature = HMAC-SHA256(payload, secret_key).                                                                                                                     | - Signature validated.<br>- Webhook processed.                                                                  |
| **HOOK-PR-003** | Invalid HMAC Signature                 | **System**     | Webhook configured       | 1. Channel sends webhook with incorrect signature.<br>2. Process webhook.                                                                                                                                                                    | - 401 Unauthorized.<br>- Error: "Webhook signature verification failed".<br>- Event NOT processed.              |
| **HOOK-PR-004** | Missing HMAC Signature                 | **System**     | Webhook configured       | 1. Channel sends webhook without X-Webhook-Signature header.<br>2. Process webhook.                                                                                                                                                          | - 401 Unauthorized if secret_key set.<br>- Process if no secret (warning logged).                               |
| **HOOK-PR-005** | Process Order Updated Event            | **System**     | Order exists             | 1. Channel sends order.updated webhook.<br>2. Payload: updated order data.                                                                                                                                                                   | - Existing order updated.<br>- Status, amounts, etc. synced.<br>- Updated_at timestamp set.                     |
| **HOOK-PR-006** | Process Order Cancelled Event          | **System**     | Order exists             | 1. Channel sends order.cancelled webhook.<br>2. Payload: order ID.                                                                                                                                                                           | - Order status → Cancelled.<br>- Cancellation reason stored.                                                    |
| **HOOK-PR-007** | Duplicate Webhook Prevention           | **System**     | Webhook already processed | 1. Channel sends same webhook twice.<br>2. Idempotency check.                                                                                                                                                                               | - **Option A**: Duplicate detected, ignored.<br>- **Option B**: Re-processed (idempotent operations).           |
| **HOOK-PR-008** | Webhook Rate Limiting                  | **System**     | Multiple webhooks        | 1. Channel sends 100 webhooks in 1 minute.                                                                                                                                                                                                   | - Rate limit: 60-120 req/min.<br>- Excess requests: 429 Too Many Requests.                                      |
| **HOOK-PR-009** | Update last_triggered_at               | **System**     | Webhook processed        | 1. Webhook successfully processed.<br>2. Check webhook record.                                                                                                                                                                               | - last_triggered_at updated to current timestamp.                                                               |
| **HOOK-PR-010** | Webhook Error Handling                 | **System**     | Malformed payload        | 1. Channel sends invalid JSON.<br>2. Process webhook.                                                                                                                                                                                        | - 400 Bad Request.<br>- Error logged.<br>- No database changes.                                                 |

### 4.4 Test Webhook

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **HOOK-TS-001** | Send Test Event            | **Admin**      | Webhook configured     | 1. View webhook details.<br>2. Click **"Send Test Event"**.<br>3. Select event type.<br>4. Send.                                                                                                                                            | - Test webhook sent to endpoint.<br>- Response shown (success/error).<br>- Test event logged.                  |
| **HOOK-TS-002** | View Test Results          | **Admin**      | Test sent              | 1. View test results popup.                                                                                                                                                                                                                  | - Request payload shown.<br>- Response status and body displayed.<br>- Signature verification result.           |

---

## 5. Validation Tests

| ID              | Title                       | Role           | Pre-conditions    | Detailed Test Steps          | Expected Result                                        |
|-----------------|-----------------------------|----------------|-------------------|------------------------------|--------------------------------------------------------|
| **VAL-001**     | Order - Tenant ID Required  | **System**     | Creating order    | 1. Leave tenant_id empty.    | - Auto-set from current tenant context.                |
| **VAL-002**     | Order - Channel ID Required | **System**     | Creating order    | 1. Leave channel_id empty.   | - Error: "The channel id field is required".           |
| **VAL-003**     | Order - Order Number Unique | **System**     | Creating order    | 1. Duplicate order_number for same tenant+channel. | - **Option A**: Update existing.<br>- **Option B**: Unique constraint violation. |
| **VAL-004**     | Order - Status Enum         | **System**     | Creating/updating | 1. status: "invalid".        | - Valid: pending, processing, completed, cancelled, refunded. |
| **VAL-005**     | Order - Payment Status Enum | **System**     | Creating/updating | 1. payment_status: "invalid". | - Valid: unpaid, paid, partially_paid, refunded.       |
| **VAL-006**     | Order - Total Amount Positive | **System**   | Creating order    | 1. total_amount: -100.       | - Error: "Total amount must be positive".              |
| **VAL-007**     | Item - Price Positive       | **System**     | Creating item     | 1. price: -50.               | - Error: "Price must be positive".                     |
| **VAL-008**     | Item - Quantity Positive    | **System**     | Creating item     | 1. quantity: 0.              | - Error: "Quantity must be at least 1".                |
| **VAL-009**     | Webhook - Event Types Array | **System**     | Creating webhook  | 1. event_types: null.        | - Error: "Event types must be an array".               |
| **VAL-010**     | Sync Log - Status Enum      | **System**     | Creating log      | 1. status: "invalid".        | - Valid: in_progress, completed, failed.               |

---

## 6. Permission & RBAC Tests

| ID               | Title                  | Role           | Pre-conditions      | Detailed Test Steps             | Expected Result                            |
|------------------|------------------------|----------------|---------------------|---------------------------------|--------------------------------------------|
| **PERM-001**     | Admin Full Access      | **Admin**      | Logged in           | 1. Access all order features.   | - All CRUD operations allowed.             |
| **PERM-002**     | Order Mgr - View Orders | **Order Mgr**  | Logged in          | 1. View orders list.            | - Allowed with `order.orders.view`.        |
| **PERM-003**     | Order Mgr - Edit Orders | **Order Mgr**  | Order exists       | 1. Edit order status.           | - Allowed with `order.orders.edit`.        |
| **PERM-004**     | Warehouse - Limited Edit | **Warehouse** | Order exists       | 1. Edit tracking number only.   | - Allowed for tracking_number only.        |
| **PERM-005**     | Order Mgr - Sync Access | **Order Mgr**  | Logged in          | 1. Trigger manual sync.         | - Allowed with `order.sync.manual-sync`.   |
| **PERM-006**     | Order Mgr - View Profitability | **Order Mgr** | Orders exist   | 1. View profitability dashboard. | - Allowed with `order.profitability.view`. |
| **PERM-007**     | Admin - Webhook Management | **Admin**    | Logged in          | 1. Create/edit webhooks.        | - Allowed with `order.webhooks.manage`.    |
| **PERM-008**     | Read Only - View Only  | **Read Only**  | Logged in           | 1. View orders.                 | - View allowed.<br>- Edit/Delete: 403.     |
| **PERM-009**     | Unauthorized - Create  | **Read Only**  | Logged in           | 1. Try to create order.         | - 403 Forbidden.                           |
| **PERM-010**     | Unauthorized - Sync    | **Read Only**  | Logged in           | 1. Try to trigger sync.         | - 403 Forbidden.                           |

---

## 7. Multi-Tenancy & Company Isolation

| ID              | Title                           | Role           | Pre-conditions | Detailed Test Steps                        | Expected Result           |
|-----------------|---------------------------------|----------------|----------------|--------------------------------------------|---------------------------|
| **TENANT-001**  | Cross-Tenant Access             | **Admin**      | Tenant A       | 1. Try to access Tenant B orders.          | - 404 Not Found.          |
| **TENANT-002**  | Tenant Isolation - Orders       | **Order Mgr**  | Tenant A       | 1. List orders.                            | - Only Tenant A orders.   |
| **TENANT-003**  | Tenant ID Auto-Assigned         | **System**     | Creating order | 1. Create order.<br>2. Check tenant_id.    | - Auto-set from context.  |
| **TENANT-004**  | Composite Unique Constraint     | **System**     | Order exists   | 1. Create duplicate (tenant, channel, channel_order_id). | - **Option A**: Update existing.<br>- **Option B**: Unique violation. |
| **TENANT-005**  | Channel from Different Tenant   | **Order Mgr**  | Tenant A       | 1. Sync from Tenant B channel.             | - Error: Channel not found (tenant filter). |

---

## 8. Edge Cases & Hidden Scenarios

| ID               | Title                           | Role           | Pre-conditions            | Detailed Test Steps                             | Expected Result                                                                                      |
|------------------|---------------------------------|----------------|---------------------------|-------------------------------------------------|------------------------------------------------------------------------------------------------------|
| **EDGE-001**     | Order with Zero Total           | **System**     | Free product order        | 1. Order: total_amount = 0.00.                  | - Accepted (free order/sample).<br>- Payment status: paid or N/A.                                    |
| **EDGE-002**     | Order with Many Items           | **System**     | Large order               | 1. Order with 100+ items.                       | - All items stored.<br>- No limit on item count.                                                     |
| **EDGE-003**     | Duplicate Order Number          | **System**     | Order exists              | 1. Same order_number from channel.              | - **Option A**: Update existing (upsert logic).<br>- **Option B**: Unique constraint.                |
| **EDGE-004**     | Missing Customer Email          | **System**     | Guest checkout            | 1. Order without customer_email.                | - customer_email: null (allowed).<br>- Guest order indicator.                                        |
| **EDGE-005**     | JSON Address Format             | **System**     | Syncing order             | 1. shipping_address: `{street, city, country, zip}`. | - JSON stored correctly.<br>- Display formatted in UI.                                               |
| **EDGE-006**     | Multi-Currency Orders           | **System**     | Various currencies        | 1. Orders in USD, EUR, SAR.                     | - Each currency stored separately.<br>- Currency code displayed.                                     |
| **EDGE-007**     | Order Sync - API Timeout        | **System**     | Slow channel API          | 1. Sync request times out.                      | - Timeout after X seconds.<br>- Status: Failed.<br>- Error: "API timeout".                           |
| **EDGE-008**     | Order Sync - Rate Limited       | **System**     | Channel rate limit        | 1. Channel API returns 429.                     | - Backoff and retry.<br>- Logged: "Rate limited by channel".                                         |
| **EDGE-009**     | Webhook - Invalid JSON          | **System**     | Malformed payload         | 1. Webhook with invalid JSON.                   | - 400 Bad Request.<br>- Error: "Invalid JSON payload".                                               |
| **EDGE-010**     | Webhook - Unknown Event Type    | **System**     | Webhook configured        | 1. Event type not in selected events.           | - Webhook ignored.<br>- Log: "Event type not subscribed".                                            |
| **EDGE-011**     | Channel Deleted                 | **Admin**      | Orders exist              | 1. Delete channel.<br>2. Check orders.          | - Orders remain.<br>- channel_id: null or orphan (FK behavior).                                      |
| **EDGE-012**     | Product Deleted                 | **Admin**      | Order items exist         | 1. Delete product.<br>2. Check order items.     | - Items remain.<br>- product_id: null or orphan.<br>- product_name still stored.                     |
| **EDGE-013**     | Unicode in Customer Name        | **System**     | Syncing order             | 1. customer_name: "محمد أحمد 😊".               | - Unicode/emoji stored correctly.                                                                    |
| **EDGE-014**     | Very Large Order Total          | **System**     | High-value order          | 1. total_amount: 999,999,999.9999.              | - Accepted (DECIMAL 15,4).                                                                           |
| **EDGE-015**     | Profitability - Missing Costs   | **Order Mgr**  | Order, no product costs   | 1. Calculate profitability.                     | - Cost: 0.00 or N/A.<br>- Warning: "Cost data incomplete".                                           |
| **EDGE-016**     | Sync Duplicate Prevention       | **System**     | Order synced              | 1. Sync same order twice in short time.         | - **Option A**: Update existing (idempotent).<br>- **Option B**: Duplicate check by channel_order_id. |

---

## 9. API Contract & Pagination Tests

| ID              | Title              | Role      | Pre-conditions | Detailed Test Steps                   | Expected Result                                      |
|-----------------|--------------------|-----------|----------------|---------------------------------------|------------------------------------------------------|
| **API-001**     | Default Pagination | **Admin** | 50+ orders     | 1. GET `/api/v1/order/orders`.        | - 20 items per page.<br>- Pagination meta included.  |
| **API-002**     | Custom Limit       | **Admin** | Many orders    | 1. GET `?limit=50`.                   | - 50 items returned.<br>- Max 100 enforced.          |
| **API-003**     | Combined Filters   | **Admin** | Various data   | 1. GET `?channel_id=x&status=completed`. | - Both filters applied.                           |
| **API-004**     | Response Structure | **Admin** | Orders exist   | 1. GET orders list.                   | - `{success: true, data: [...], meta: {...}}`        |
| **API-005**     | Order with Relations | **Admin** | Order exists  | 1. GET `/api/v1/order/orders/{id}?include=items,channel`. | - Order with orderItems and channel populated. |
| **API-006**     | Profitability Endpoint | **API** | Order exists  | 1. POST `/api/v1/order/profitability/calculate`. | - JSON: `{revenue, cost, profit, marginPercentage, itemBreakdown}`. |
| **API-007**     | Webhook Receiver Endpoint | **API** | Webhook configured | 1. POST `/api/v1/order/webhooks/receive/{channel}`. | - 200 OK if valid.<br>- 401 if signature fails.  |

---

## 10. Integration Tests

| ID              | Title                           | Role           | Pre-conditions            | Detailed Test Steps                             | Expected Result                                                                                      |
|-----------------|---------------------------------|----------------|---------------------------|-------------------------------------------------|------------------------------------------------------------------------------------------------------|
| **INTEG-001**   | Sync → Order Created → Profitability | **System** | Channel configured    | 1. Sync orders.<br>2. Orders created.<br>3. Calculate profitability.<br>4. Uses ProductCost from Pricing package. | - Full flow works.<br>- Cost data from EPIC-002 (Pricing) used correctly.                            |
| **INTEG-002**   | Webhook → Order Updated → Event | **System**   | Webhook configured       | 1. Channel sends webhook.<br>2. Order updated.<br>3. OrderStatusUpdated event dispatched.<br>4. Listener updates profitability. | - End-to-end webhook flow.<br>- Event system works.<br>- Listeners execute.                         |
| **INTEG-003**   | Manual Sync → Sync Log → Retry | **Order Mgr** | Sync failed              | 1. Manual sync fails.<br>2. Sync log created (status: failed).<br>3. Retry sync.<br>4. Sync succeeds. | - Retry mechanism works.<br>- Sync log updated.                                                      |
| **INTEG-004**   | Multi-Channel Profitability     | **Order Mgr** | Orders from 3 channels   | 1. View profitability by channel.<br>2. Each channel has different costs. | - Channel-specific profitability calculated.<br>- Channel costs applied correctly.                   |

---

## Verification Checklist

### Unified Orders
- [ ] **CRUD**: All operations work for unified orders
- [ ] **Validation**: tenant_id, channel_id, channel_order_id, order_number required
- [ ] **Validation**: Composite unique (tenant_id, channel_id, channel_order_id)
- [ ] **Status Enum**: pending, processing, completed, cancelled, refunded
- [ ] **Payment Status Enum**: unpaid, paid, partially_paid, refunded
- [ ] **Multi-Currency**: Currency code stored per order
- [ ] **JSON Fields**: shipping_address, billing_address stored as JSON
- [ ] **Tenant Isolation**: Orders scoped by tenant_id
- [ ] **Channel Relationship**: Populated correctly

### Order Items
- [ ] **CRUD**: All operations work for order items
- [ ] **Validation**: product_id, product_name, SKU, quantity, price required
- [ ] **Profitability Fields**: cost_basis, profit_amount calculated
- [ ] **Product Relationship**: product_id foreign key
- [ ] **Decimal Precision**: DECIMAL(15,4) for amounts

### Order Synchronization
- [ ] **Manual Sync**: Single channel and all channels work
- [ ] **Sync Modes**: Full and incremental sync
- [ ] **Date Filters**: Sync within date range
- [ ] **Sync Logs**: Created with status, statistics, error details
- [ ] **Retry**: Failed syncs can be retried
- [ ] **Channel Adapters**: Salla, Shopify, WooCommerce
- [ ] **Events**: OrderSynced, SyncFailed dispatched
- [ ] **Automatic Sync**: Scheduled sync via cron

### Profitability Analysis
- [ ] **Order Profitability**: Revenue, cost, profit, margin % calculated
- [ ] **Item Profitability**: Per-item profit breakdown
- [ ] **Integration**: Uses ProductCost from Pricing package
- [ ] **Dashboard**: Summary cards, charts (Chart.js)
- [ ] **By Channel**: Channel comparison works
- [ ] **By Product**: Product profitability analysis
- [ ] **Export**: Reports downloadable as CSV/Excel

### Webhooks
- [ ] **CRUD**: All operations work for webhooks
- [ ] **Secret Key**: Auto-generated on creation
- [ ] **Event Types**: Multi-select, array storage
- [ ] **HMAC Verification**: SHA256 signature check
- [ ] **Webhook Processing**: order.created, updated, cancelled, etc.
- [ ] **Rate Limiting**: 60-120 req/min enforced
- [ ] **last_triggered_at**: Updated on webhook receipt
- [ ] **Test Webhook**: Send test event works

### Permissions
- [ ] **Admin**: Full access to all features
- [ ] **Order Manager**: View, edit, sync, profitability
- [ ] **Warehouse**: Limited edit (tracking number)
- [ ] **Read Only**: View-only access
- [ ] **ACL**: 28 permissions enforced

### Multi-Tenancy
- [ ] **Tenant Isolation**: All models scoped by tenant_id
- [ ] **Cross-Tenant**: Access prevented
- [ ] **Auto-Assignment**: tenant_id set from context
- [ ] **Composite Unique**: (tenant_id, channel_id, channel_order_id)

### API
- [ ] **Pagination**: Default 20, max 100 per page
- [ ] **Filtering**: channel_id, status, payment_status, date_range
- [ ] **Response Format**: Consistent JSON structure
- [ ] **Error Handling**: Proper status codes and messages
- [ ] **Webhook Endpoint**: Public, rate-limited, HMAC verified
- [ ] **Profitability Endpoint**: Calculate API works

### Integration
- [ ] **Pricing Package**: ProductCost integration works
- [ ] **Event System**: Events dispatched, listeners execute
- [ ] **Channel Adapters**: External API integrations work
- [ ] **Multi-Channel**: Different channels work independently
