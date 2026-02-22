# UnoPim - User Flows Documentation

## Document Information

| Field        | Value                                          |
| ------------ | ---------------------------------------------- |
| Module       | Pricing & Order Management                     |
| Date         | 2026-02-18                                     |
| Version      | 1.0                                            |
| Packages     | Webkul/Pricing, Webkul/Order                   |

---

## Table of Contents

1. [Pricing Management Flows](#1-pricing-management-flows)
2. [Order Management Flows](#2-order-management-flows)
3. [Integration Flows](#3-integration-flows)
4. [Administrative Flows](#4-administrative-flows)

---

## 1. Pricing Management Flows

### Flow 1.1: Setting Up Product Costs

**Actor**: Pricing Manager
**Goal**: Configure cost structure for a product
**Preconditions**: Product exists in catalog

**Steps**:

1. **Navigate to Product Costs**
   - Login as Pricing Manager
   - Navigate to **Pricing** > **Product Costs**
   - View: Product costs DataGrid

2. **Create Base Cost (COGS)**
   - Click **"Create Product Cost"** button
   - Form appears with fields:
     - Product: [Search and select product]
     - Cost Type: COGS (selected)
     - Amount: 750.00
     - Currency: USD
     - Effective From: Today
     - Effective To: [Optional]
   - Click **"Save"**
   - Toast: "Product cost created successfully"
   - Redirect to costs list

3. **Add Operational Costs**
   - Click **"Create Product Cost"** again
   - Select same product
   - Cost Type: Operational
   - Amount: 50.00
   - Currency: USD
   - Effective From: Today
   - Save

4. **Add Shipping Costs**
   - Repeat for Cost Type: Shipping
   - Amount: 25.00
   - Save

5. **Verify Total Costs**
   - View product costs list
   - Filter by product
   - See all cost types listed
   - Total cost = COGS + Operational + Shipping = 825.00

**Success Criteria**:
- ✅ All cost types created successfully
- ✅ Costs visible in DataGrid
- ✅ Product has complete cost structure
- ✅ Ready for break-even calculation

**Alternative Flows**:
- **A1**: Historical costs - Set Effective To date for old costs
- **A2**: Multi-currency - Create costs in EUR, SAR for different markets

---

### Flow 1.2: Configuring Channel Costs

**Actor**: Channel Manager
**Goal**: Set up channel fees and commissions
**Preconditions**: Channel configured in system

**Steps**:

1. **Navigate to Channel Costs**
   - Login as Channel Manager
   - Navigate to **Pricing** > **Channel Costs**

2. **Create Salla Commission**
   - Click **"Create Channel Cost"**
   - Form fields:
     - Channel: Salla Store
     - Fee Type: Commission
     - Fee Structure: Percentage
     - Value: 2.5 (%)
     - Currency: SAR
     - Effective From: Today
   - Save
   - Toast: "Channel cost created successfully"

3. **Create Payment Processing Fee**
   - Click **"Create Channel Cost"**
   - Channel: Salla Store
   - Fee Type: Payment Processing
   - Fee Structure: Fixed Amount
   - Value: 2.00
   - Currency: SAR
   - Save

4. **Create Tiered Commission (Advanced)**
   - Click **"Create Channel Cost"**
   - Channel: Shopify Store
   - Fee Type: Commission
   - Fee Structure: Tiered
   - Add Tiers:
     - Tier 1: 0-1000 SAR = 3%
     - Tier 2: 1001-5000 SAR = 2.5%
     - Tier 3: 5001+ SAR = 2%
   - Save

5. **Verify Channel Costs**
   - View channel costs list
   - Filter by channel
   - All fee types visible

**Success Criteria**:
- ✅ Channel costs configured
- ✅ Multiple fee structures supported
- ✅ Tiered pricing works
- ✅ Ready for break-even calculation

---

### Flow 1.3: Calculating Break-Even Price

**Actor**: Pricing Manager
**Goal**: Determine minimum profitable price
**Preconditions**: Product costs and channel costs configured

**Steps**:

1. **Access Break-Even Calculator**
   - Login as Pricing Manager
   - Navigate to **Pricing** > **Break-Even Calculator**

2. **Select Product and Channel**
   - Form fields:
     - Product: [Search] iPhone 15 Pro
     - Channel: Salla Store
   - Click **"Calculate"**

3. **System Calculation**
   - Backend formula: `breakEven = fixedCosts / (1 - variableRate)`
   - Fixed costs retrieved:
     - COGS: 750.00
     - Operational: 50.00
     - Shipping: 25.00
     - Total Fixed: 825.00
   - Variable costs retrieved:
     - Salla Commission: 2.5%
     - Payment Processing: 2.00 (converted to %)
     - Total Variable Rate: ~2.7%
   - Calculation: 825 / (1 - 0.027) = 848.02

4. **View Results**
   - Results card displays:
     - **Break-Even Price**: 848.02 SAR
     - **Fixed Costs**: 825.00 SAR
     - **Variable Rate**: 2.7%
     - **Breakdown**:
       - COGS: 750.00
       - Operational: 50.00
       - Shipping: 25.00
       - Channel Commission: 2.5%
       - Payment Fee: 2.00
   - Chart visualization (optional)

5. **Export Results**
   - Click **"Export"** button
   - Download PDF/CSV with breakdown
   - Share with stakeholders

**Success Criteria**:
- ✅ Break-even price calculated correctly
- ✅ All costs included
- ✅ Formula applied accurately
- ✅ Results exportable

**Edge Cases**:
- **E1**: Variable rate = 100% → Error message
- **E2**: No costs configured → Break-even = 0 or error
- **E3**: Multi-currency → Convert to base currency

---

### Flow 1.4: Creating Pricing Strategy with Psychological Pricing

**Actor**: Pricing Manager
**Goal**: Set competitive price with psychological appeal
**Preconditions**: Break-even price calculated

**Steps**:

1. **Navigate to Pricing Strategies**
   - Login as Pricing Manager
   - Navigate to **Pricing** > **Strategies**

2. **Create Cost-Plus Strategy**
   - Click **"Create Strategy"**
   - Form fields:
     - Product: iPhone 15 Pro
     - Channel: Salla Store
     - Strategy Type: Cost-Plus
     - Markup Percentage: 25%
     - Base Cost: 848.02 (auto-filled from break-even)
   - Calculate: 848.02 × 1.25 = 1060.03

3. **Apply Psychological Pricing**
   - Enable: ☑ Use Psychological Pricing
   - Rounding Method: .99
   - System calculates: 1060.03 → 1059.99

4. **Review Final Price**
   - Preview card shows:
     - **Base Cost**: 848.02 SAR
     - **Markup**: 212.01 SAR (25%)
     - **Pre-Rounding**: 1060.03 SAR
     - **Final Price**: 1059.99 SAR
     - **Profit Margin**: ~25%
   - Comparison with competitors (if data available)

5. **Save Strategy**
   - Click **"Save Strategy"**
   - Toast: "Pricing strategy created successfully"
   - Strategy applied to product-channel combo

6. **Verify in Product Catalog**
   - Navigate to **Catalog** > **Products**
   - View product
   - Check price for Salla channel: 1059.99 SAR

**Success Criteria**:
- ✅ Strategy created with markup
- ✅ Psychological pricing applied
- ✅ Final price ends in .99
- ✅ Price synced to catalog

**Alternative Rounding Methods**:
- **.95**: 1060.03 → 1059.95
- **.00**: 1060.03 → 1060.00 (round up)
- **none**: 1060.03 (unchanged)

---

### Flow 1.5: Margin Protection Approval Workflow

**Actor**: Pricing Manager (Requester), Admin (Approver)
**Goal**: Request approval for below-margin pricing
**Preconditions**: Pricing strategy exists, competitor offers lower price

**Steps (Part 1 - Request)**:

1. **Identify Need for Price Reduction**
   - Competitor selling same product at 999.00 SAR
   - Current price: 1059.99 SAR
   - Proposed price: 995.00 SAR (below break-even margin)

2. **Navigate to Margin Protection**
   - Login as Pricing Manager
   - Navigate to **Pricing** > **Margin Protection**
   - Click **"Request Approval"**

3. **Create Protection Event**
   - Form fields:
     - Product: iPhone 15 Pro
     - Channel: Salla Store
     - Event Type: Competitive Pricing
     - Original Price: 1059.99 SAR
     - Proposed Price: 995.00 SAR
     - Margin Impact: -7.8% (below threshold)
     - Reason: "Competitor XYZ offering at 999 SAR. Need to match to maintain market share."
     - Expiration: 7 days
   - Click **"Submit Request"**
   - Status: **Blocked**
   - Toast: "Margin protection request submitted. Awaiting approval."

4. **Email Notification Sent**
   - Admin receives email:
     - Subject: "Margin Protection Approval Required"
     - Details: Product, proposed price, reason
     - Link to approval page

**Steps (Part 2 - Approval)**:

5. **Admin Reviews Request**
   - Login as Admin
   - Navigate to **Pricing** > **Margin Protection** > **Pending Approvals**
   - View request details:
     - Product info
     - Current vs. proposed price
     - Break-even analysis
     - Margin impact chart
     - Requester's reason

6. **Evaluate Request**
   - Check competitor pricing (external research)
   - Review business justification
   - Assess strategic importance
   - Decision: **Approve**

7. **Approve Request**
   - Click **"Approve"** button
   - Add approval note: "Approved for 30-day promotional period. Monitor competitor pricing weekly."
   - Confirm approval
   - Status: **Blocked** → **Approved**
   - approved_by: Admin ID
   - approved_at: Current timestamp

8. **System Updates Price**
   - Pricing strategy updated to 995.00 SAR
   - Price synced to catalog
   - Margin protection event closed
   - Email notification to requester

9. **Requester Verification**
   - Pricing Manager receives approval email
   - Views updated pricing strategy
   - Confirms new price active: 995.00 SAR

**Success Criteria**:
- ✅ Request created with blocked status
- ✅ Admin notified
- ✅ Approval workflow completed
- ✅ Price updated after approval
- ✅ Audit trail maintained

**Alternative Flow (Rejection)**:
- **A1**: Admin clicks **"Reject"**
- Rejection reason: "Margin too low. Explore cost reduction instead."
- Status: **Blocked** → **Rejected**
- Email to requester
- Price remains unchanged

**Edge Case (Expiration)**:
- **E1**: No action taken within 7 days
- System auto-rejects: Status → **Rejected**
- Reason: "Request expired"
- Email notification sent

---

## 2. Order Management Flows

### Flow 2.1: Viewing Multi-Channel Orders

**Actor**: Order Manager
**Goal**: Monitor orders from all sales channels
**Preconditions**: Orders synced from channels

**Steps**:

1. **Access Orders Dashboard**
   - Login as Order Manager
   - Navigate to **Orders** > **All Orders**
   - View: Unified orders DataGrid

2. **Explore Dashboard Interface**
   - Summary cards at top:
     - **Total Orders**: 1,247
     - **Pending**: 48
     - **Processing**: 112
     - **Completed**: 1,087
     - **Total Revenue**: 1,245,890 SAR
   - Orders table with columns:
     - Order Number
     - Channel (Salla/Shopify/WooCommerce badges)
     - Customer Email
     - Status (color-coded)
     - Payment Status
     - Total Amount
     - Order Date
     - Last Synced

3. **Filter by Channel**
   - Click filter dropdown
   - Select: Channel = "Salla Store"
   - Apply filter
   - Table shows only Salla orders
   - Summary cards recalculate

4. **Filter by Status**
   - Additional filter: Status = "Pending"
   - Combined filters: Salla + Pending
   - See 15 pending Salla orders

5. **Search Specific Order**
   - Clear filters
   - Search box: Enter order number "SLA-2026-0001"
   - Press Enter
   - Single order displayed

6. **View Order Details**
   - Click order row
   - Redirect to order detail page (see Flow 2.2)

**Success Criteria**:
- ✅ All channels visible in single view
- ✅ Filters work independently and combined
- ✅ Search finds orders quickly
- ✅ Summary cards accurate
- ✅ Color-coded status clear

---

### Flow 2.2: Viewing Detailed Order Information

**Actor**: Order Manager
**Goal**: Review complete order details including profitability
**Preconditions**: Order exists in system

**Steps**:

1. **Navigate to Order**
   - From orders list, click order "SLA-2026-0001"
   - Or navigate directly: `/admin/orders/orders/{id}`

2. **View Order Summary Card**
   - Top section displays:
     - **Order Number**: SLA-2026-0001
     - **Channel**: Salla Store (icon)
     - **Status**: Processing (yellow badge)
     - **Payment Status**: Paid (green badge)
     - **Order Date**: 2026-02-15 10:30 AM
     - **Last Synced**: 2026-02-17 08:00 AM
   - Action buttons:
     - **Edit Order**
     - **Print Invoice**
     - **Export**

3. **View Customer Information**
   - Customer info card:
     - **Name**: Ahmed Mohammed
     - **Email**: ahmed@email.com
     - **Phone**: +966 50 123 4567
     - Link to customer profile

4. **View Shipping & Billing Addresses**
   - Two-column layout:
     - **Shipping Address**:
       - Street: King Fahd Road
       - City: Riyadh
       - State: Riyadh Province
       - Country: Saudi Arabia
       - ZIP: 12345
     - **Billing Address**:
       - [Same as shipping or different]

5. **Review Order Items Table**
   - Items table with columns:
     - **Product**: Image + Name
     - **SKU**: Product SKU
     - **Quantity**: 2
     - **Price**: 1,059.99 SAR each
     - **Subtotal**: 2,119.98 SAR
     - **Cost Basis**: 1,696.04 SAR (from ProductCost)
     - **Profit**: 423.94 SAR
   - Multiple items listed
   - Totals row at bottom

6. **Check Profitability Sidebar**
   - Right sidebar card:
     - **Total Revenue**: 2,119.98 SAR
     - **Total Cost**: 1,696.04 SAR
     - **Total Profit**: 423.94 SAR
     - **Profit Margin**: 20.0% (green if positive, red if negative)
   - Progress bar visual
   - Breakdown link

7. **View Order Timeline**
   - Scroll down to timeline section
   - Chronological events:
     - ✅ Order Created (2026-02-15 10:30)
     - ✅ Payment Received (2026-02-15 10:32)
     - ✅ Order Synced from Salla (2026-02-15 11:00)
     - ✅ Status Changed to Processing (2026-02-16 09:00)
     - ⏳ Awaiting Shipment

**Success Criteria**:
- ✅ All order data visible
- ✅ Customer info complete
- ✅ Addresses formatted correctly
- ✅ Items with profitability shown
- ✅ Timeline clear and chronological
- ✅ Profitability accurate

---

### Flow 2.3: Editing Order Status & Tracking

**Actor**: Warehouse Staff
**Goal**: Update order status and add tracking number
**Preconditions**: Order in Processing status

**Steps**:

1. **Navigate to Order**
   - Login as Warehouse Staff
   - Navigate to **Orders** > **All Orders**
   - Filter: Status = "Processing"
   - Find order "SLA-2026-0001"
   - Click to view

2. **Click Edit Order**
   - On order detail page
   - Click **"Edit Order"** button
   - Redirect to edit form

3. **View Edit Form**
   - Fields available:
     - ✅ **Status**: Dropdown (editable)
     - ✅ **Tracking Number**: Text input (editable)
     - ✅ **Internal Notes**: Textarea (editable)
     - ❌ **Customer Info**: Read-only (synced from channel)
     - ❌ **Order Items**: Read-only (synced from channel)
     - ❌ **Amounts**: Read-only

4. **Update Status**
   - Current: Processing
   - Change to: Completed
   - Status dropdown options:
     - Pending
     - Processing
     - **Completed** ← Select this
     - Cancelled
     - Refunded

5. **Add Tracking Number**
   - Field: Tracking Number
   - Enter: "ARAMEX-123456789"
   - Courier: Aramex (optional)

6. **Add Internal Note**
   - Notes field:
   - Enter: "Shipped via Aramex Express. Customer requested morning delivery."

7. **Save Changes**
   - Click **"Save Order"** button
   - System validates:
     - Status change allowed
     - Tracking number format
   - Backend updates:
     - Status: Processing → Completed
     - Tracking: ARAMEX-123456789
     - Notes: [saved]
     - Updated_at: Current timestamp
   - Event dispatched: `OrderStatusUpdated`

8. **View Success**
   - Toast: "Order updated successfully"
   - Redirect to order detail page
   - Status badge: Completed (green)
   - Tracking number displayed
   - Timeline updated with new entry

9. **Customer Notification (Automated)**
   - System sends email to customer:
     - Subject: "Your Order SLA-2026-0001 Has Shipped"
     - Body: Tracking number, courier, expected delivery
     - Link to tracking page

**Success Criteria**:
- ✅ Status updated to Completed
- ✅ Tracking number saved
- ✅ Notes added
- ✅ Customer notified
- ✅ Timeline updated
- ✅ Event dispatched

**Permission Check**:
- ❌ Warehouse staff cannot edit: customer info, amounts, items
- ✅ Warehouse staff can edit: status, tracking, notes
- ✅ Pricing manager cannot edit orders (403 Forbidden)

---

### Flow 2.4: Manual Order Synchronization

**Actor**: Order Manager
**Goal**: Sync recent orders from Salla channel
**Preconditions**: Salla channel configured with API credentials

**Steps**:

1. **Navigate to Sync Interface**
   - Login as Order Manager
   - Navigate to **Orders** > **Sync** > **Manual Sync**
   - View manual sync form

2. **Configure Sync Parameters**
   - Form fields:
     - **Channel**: Dropdown → Select "Salla Store"
     - **Sync Mode**: Radio buttons
       - ⚪ Full Sync (all orders)
       - 🔵 Incremental Sync (only new/updated) ← Selected
     - **Date Range**:
       - From: 2026-02-10
       - To: 2026-02-17 (today)
     - **Filters** (advanced):
       - Status: All
       - Payment Status: All

3. **Initiate Sync**
   - Click **"Start Sync"** button
   - Confirmation modal:
     - "Sync 25-50 orders from Salla Store?"
     - Estimated time: 2-5 minutes
   - Click **"Confirm"**

4. **Sync Job Dispatched**
   - System creates sync log record:
     - channel_id: Salla Store ID
     - status: "in_progress"
     - started_at: Current timestamp
   - Background job queued: `SyncChannelOrders`
   - Toast: "Sync started for Salla Store. View progress in Sync Logs."
   - Redirect to Sync Logs page

5. **Monitor Sync Progress**
   - Sync Logs page shows active sync:
     - Channel: Salla Store
     - Status: In Progress (spinner)
     - Records Synced: 15 / ~30 (updating)
     - Started At: 2026-02-17 14:30:00
     - Progress bar: 50%

6. **Background Processing**
   - Job executes:
     - Calls Salla API: `GET /orders?since={last_synced_at}`
     - Receives 28 orders
     - For each order:
       - Check if exists (by channel_order_id)
       - Create or update UnifiedOrder
       - Create UnifiedOrderItems
       - Calculate profitability
       - Increment synced counter
   - Handle errors gracefully (continue on item failure)

7. **Sync Completion**
   - Job finishes:
     - records_synced: 28
     - records_failed: 0
     - status: "in_progress" → "completed"
     - completed_at: Current timestamp
   - Event dispatched: `OrderSynced`
   - Listeners execute:
     - UpdateOrderProfitability
     - UpdateProductSalesMetrics

8. **View Sync Results**
   - Sync Logs page auto-refreshes (or manual refresh)
   - Status: Completed (green checkmark)
   - Statistics:
     - **Records Synced**: 28
     - **Records Failed**: 0
     - **Duration**: 2m 15s
     - **Success Rate**: 100%
   - Click sync log row to view details

9. **Review Sync Log Details**
   - Detailed sync log page shows:
     - Full sync parameters
     - Statistics breakdown:
       - New orders: 18
       - Updated orders: 10
       - Failed orders: 0
     - Error log: (empty - no errors)
     - Actions:
       - **Retry** (disabled - already completed)
       - **Export Log** (CSV)

10. **Verify Orders Synced**
    - Navigate to **Orders** > **All Orders**
    - Filter: Channel = Salla, Order Date = Last 7 days
    - See 28 orders listed
    - Check profitability metrics updated

**Success Criteria**:
- ✅ Sync completed successfully
- ✅ All 28 orders synced
- ✅ No errors
- ✅ Profitability calculated
- ✅ Sync log created with statistics
- ✅ Events dispatched

**Error Handling Flow**:
- **E1**: API Authentication fails
  - Status: Failed
  - Error: "Invalid API credentials"
  - Action: Check channel configuration
- **E2**: Network timeout
  - Retry 3 times with backoff
  - If still fails: Status = Failed
- **E3**: Partial failure (5 of 28 failed)
  - Status: Completed (with warnings)
  - records_synced: 23
  - records_failed: 5
  - Error log: Details of 5 failed orders

---

### Flow 2.5: Analyzing Profitability Dashboard

**Actor**: Order Manager
**Goal**: Understand overall profitability and identify trends
**Preconditions**: Orders with cost data exist

**Steps**:

1. **Navigate to Profitability Dashboard**
   - Login as Order Manager
   - Navigate to **Orders** > **Profitability**
   - View: Profitability analysis dashboard

2. **View Summary Cards**
   - Top row displays 4 cards:
     - **Card 1 - Total Revenue**:
       - Amount: 1,245,890 SAR
       - Trend: ↑ 15% vs last month
       - Icon: 💰
     - **Card 2 - Total Profit**:
       - Amount: 248,920 SAR
       - Trend: ↑ 12% vs last month
       - Icon: 📈
     - **Card 3 - Average Margin**:
       - Percentage: 19.98%
       - Trend: ↓ 2% vs last month
       - Icon: 📊
       - Color: Green (>15%)
     - **Card 4 - Orders Analyzed**:
       - Count: 1,247 orders
       - Date range: Last 30 days
       - Icon: 📦

3. **Analyze Revenue vs Profit Trend Chart**
   - Line chart (Chart.js):
     - X-axis: Dates (daily for last 30 days)
     - Y-axis: Amount (SAR)
     - **Blue line**: Revenue (top line)
     - **Green line**: Profit (bottom line)
     - Gap between lines: Cost
   - Hover on data point:
     - Date: Feb 15, 2026
     - Revenue: 45,230 SAR
     - Profit: 9,145 SAR
     - Margin: 20.2%
   - Observations:
     - ✅ Consistent revenue growth
     - ⚠️ Profit margin decreasing slightly

4. **Review Profit by Channel Chart**
   - Horizontal bar chart:
     - Channels (Y-axis):
       - Salla Store: 145,890 SAR profit (green bar)
       - Shopify Store: 78,450 SAR profit (blue bar)
       - WooCommerce: 24,580 SAR profit (orange bar)
     - Amount (X-axis): 0 - 150,000 SAR
   - Click on Salla bar:
     - Drill down to Salla channel details
     - Redirect to "By Channel" page (see Flow 2.6)

5. **Examine Top Profitable Products**
   - Horizontal bar chart:
     - Products (Y-axis):
       - iPhone 15 Pro: 84,230 SAR profit
       - MacBook Pro: 52,180 SAR profit
       - AirPods Pro: 18,920 SAR profit
       - iPad Air: 15,640 SAR profit
       - Apple Watch: 12,350 SAR profit
     - Amount (X-axis): 0 - 90,000 SAR
   - Click on product bar:
     - Redirect to product profitability detail

6. **Apply Date Range Filter**
   - Filter controls:
     - Preset: Last 7 days / Last 30 days / Last 90 days / Custom
     - Select: Custom
     - From: 2026-02-01
     - To: 2026-02-15
   - Click **"Apply"**
   - Dashboard recalculates:
     - Summary cards update
     - Charts redraw with new data
     - Date range badge shown

7. **Filter by Channel**
   - Additional filter: Channel = "Salla Store"
   - Apply
   - Dashboard now shows only Salla data
   - Profit by Channel chart hidden (single channel)

8. **Export Profitability Report**
   - Click **"Export Report"** button
   - Modal appears:
     - Format: CSV / Excel / PDF
     - Select: Excel
     - Include: Summary + Detailed breakdown
   - Click **"Download"**
   - File downloaded: `profitability-report-2026-02-17.xlsx`
   - Contains:
     - Sheet 1: Summary
     - Sheet 2: By Channel
     - Sheet 3: By Product
     - Sheet 4: By Date
     - Sheet 5: Raw Order Data

**Success Criteria**:
- ✅ Dashboard loads with accurate data
- ✅ All charts render correctly (Chart.js)
- ✅ Filters update dashboard dynamically
- ✅ Export works in multiple formats
- ✅ Trends and insights clear
- ✅ Interactive drill-down possible

**Insights Gained**:
- 💡 Salla channel most profitable
- 💡 iPhone 15 Pro top profit contributor
- 💡 Margin decreasing → investigate cost increases
- 💡 Consistent revenue growth → scale efforts

---

### Flow 2.6: Comparing Channel Profitability

**Actor**: Order Manager
**Goal**: Compare performance across sales channels
**Preconditions**: Orders from multiple channels

**Steps**:

1. **Navigate to Channel Comparison**
   - From profitability dashboard, click **"By Channel"** tab
   - Or navigate: **Orders** > **Profitability** > **By Channel**

2. **View Channel Cards**
   - Grid layout with cards for each channel:
     - **Card 1 - Salla Store**:
       - Icon: Salla logo
       - Order Count: 847 orders
       - Revenue: 645,230 SAR
       - Profit: 145,890 SAR
       - Margin: 22.6% (green)
       - Avg Order Value: 762 SAR
       - Status: Active (green dot)
     - **Card 2 - Shopify Store**:
       - Icon: Shopify logo
       - Order Count: 312 orders
       - Revenue: 456,780 SAR
       - Profit: 78,450 SAR
       - Margin: 17.2% (yellow)
       - Avg Order Value: 1,464 SAR
       - Status: Active
     - **Card 3 - WooCommerce**:
       - Icon: WooCommerce logo
       - Order Count: 88 orders
       - Revenue: 143,880 SAR
       - Profit: 24,580 SAR
       - Margin: 17.1% (yellow)
       - Avg Order Value: 1,635 SAR
       - Status: Active

3. **Review Detailed Comparison Table**
   - DataGrid below cards:
     - Columns:
       - Channel Name
       - Order Count
       - Revenue
       - Cost
       - Profit
       - Margin %
       - Avg Order Value
       - Top Product
       - Actions
   - Rows for each channel
   - Sortable columns (click header)

4. **Sort by Margin**
   - Click "Margin %" column header
   - Sort descending
   - Ranking:
     1. Salla Store: 22.6%
     2. Shopify Store: 17.2%
     3. WooCommerce: 17.1%

5. **Identify Best Performer**
   - Salla Store has:
     - ✅ Highest margin (22.6%)
     - ✅ Most orders (847)
     - ✅ Highest total profit (145,890 SAR)
     - Badge: "🏆 Best Performer"

6. **Investigate Low Margin Channel**
   - WooCommerce margin: 17.1% (below target 20%)
   - Click on WooCommerce row
   - Drill-down modal opens:
     - Cost breakdown:
       - COGS: 65%
       - Channel fees: 8% (high)
       - Shipping: 7%
       - Other: 3%
     - Top products sold
     - Recent orders list
   - Insight: High channel fees reducing margin
   - Action item: Negotiate WooCommerce fees or adjust pricing

7. **Compare Avg Order Value**
   - Chart: Bar chart of AOV
     - WooCommerce: 1,635 SAR (highest)
     - Shopify: 1,464 SAR
     - Salla: 762 SAR (lowest)
   - Insight: Salla has lower AOV but higher volume and margin
   - Strategy: Scale Salla, optimize WooCommerce costs

8. **Export Channel Comparison**
   - Click **"Export"** button
   - Download CSV: `channel-comparison-2026-02-17.csv`
   - Share with management

**Success Criteria**:
- ✅ All channels compared side-by-side
- ✅ Best performer identified
- ✅ Margin issues highlighted
- ✅ Actionable insights gained
- ✅ Export for reporting

**Action Items Identified**:
1. Scale Salla Store efforts (best margin)
2. Negotiate WooCommerce fee reduction
3. Increase Shopify average order value
4. Investigate Salla success factors for replication

---

## 3. Integration Flows

### Flow 3.1: End-to-End Order Profitability (Pricing + Order Integration)

**Actor**: System (Automated)
**Goal**: Calculate accurate order profitability using pricing data
**Preconditions**: Product costs configured (EPIC-002), Order synced (EPIC-006)

**Steps**:

1. **Order Synced from Channel**
   - Webhook received: `order.created` from Salla
   - Payload:
     ```json
     {
       "order_id": "salla-12345",
       "items": [
         {
           "product_id": "iphone-15-pro",
           "quantity": 2,
           "price": 1059.99
         }
       ],
       "total": 2119.98,
       "currency": "SAR"
     }
     ```

2. **UnifiedOrder Created**
   - OrderSyncService processes webhook
   - Creates UnifiedOrder:
     - tenant_id: from context
     - channel_id: Salla Store
     - channel_order_id: "salla-12345"
     - order_number: "SLA-2026-0001"
     - total_amount: 2119.98
     - currency_code: SAR
     - status: pending

3. **UnifiedOrderItems Created**
   - For each item in payload:
     - product_id: iphone-15-pro (lookup)
     - quantity: 2
     - price: 1059.99
     - subtotal: 2119.98

4. **Event Dispatched: OrderSynced**
   - Event payload:
     - order_id: [UnifiedOrder ID]
     - channel_id: Salla Store
     - synced_at: timestamp

5. **Listener: UpdateOrderProfitability**
   - Triggered by OrderSynced event
   - Calls: ProfitabilityCalculator::calculateOrderProfitability()

6. **Retrieve Product Costs (Integration with EPIC-002)**
   - For product "iphone-15-pro":
   - Query ProductCost model:
     ```sql
     SELECT * FROM product_costs
     WHERE product_id = 'iphone-15-pro'
       AND effective_from <= '2026-02-17'
       AND (effective_to IS NULL OR effective_to >= '2026-02-17')
       AND tenant_id = [current tenant]
     ```
   - Results:
     - COGS: 750.00 SAR
     - Operational: 50.00 SAR
     - Shipping: 25.00 SAR
   - Total Cost per unit: 825.00 SAR

7. **Retrieve Channel Costs (Integration with EPIC-002)**
   - For channel "Salla Store":
   - Query ChannelCost model:
     ```sql
     SELECT * FROM channel_costs
     WHERE channel_id = [salla-id]
       AND effective_from <= '2026-02-17'
       AND tenant_id = [current tenant]
     ```
   - Results:
     - Commission: 2.5% (percentage)
     - Payment Fee: 2.00 SAR (fixed)

8. **Calculate Item Profitability**
   - For iPhone 15 Pro × 2:
     - Revenue: 1059.99 × 2 = 2119.98 SAR
     - Product Cost: 825.00 × 2 = 1650.00 SAR
     - Channel Commission: 2119.98 × 0.025 = 53.00 SAR
     - Payment Fee: 2.00 SAR
     - **Total Cost**: 1650.00 + 53.00 + 2.00 = 1705.00 SAR
     - **Profit**: 2119.98 - 1705.00 = 414.98 SAR
     - **Margin %**: (414.98 / 2119.98) × 100 = 19.58%

9. **Update UnifiedOrderItem**
   - Set fields:
     - cost_basis: 1705.00 SAR
     - profit_amount: 414.98 SAR
   - Save to database

10. **Calculate Order-Level Profitability**
    - Aggregate all items:
      - Total Revenue: 2119.98 SAR
      - Total Cost: 1705.00 SAR
      - Total Profit: 414.98 SAR
      - Margin %: 19.58%

11. **Store Profitability Result**
    - Create ProfitabilityResult ValueObject:
      ```php
      new ProfitabilityResult(
        orderId: [order-id],
        revenue: 2119.98,
        totalCost: 1705.00,
        profit: 414.98,
        marginPercentage: 19.58,
        itemBreakdown: [...],
        currencyCode: 'SAR'
      )
      ```
    - Cache result (optional)

12. **Display on Order Detail Page**
    - User views order
    - Profitability sidebar shows:
      - Total Revenue: 2,119.98 SAR
      - Total Cost: 1,705.00 SAR
      - Total Profit: 414.98 SAR
      - Profit Margin: 19.58% (green)
    - Item table shows per-item profit

**Success Criteria**:
- ✅ Product costs retrieved from EPIC-002
- ✅ Channel costs retrieved from EPIC-002
- ✅ Profitability calculated accurately
- ✅ Order items updated with cost_basis and profit
- ✅ Order detail page displays profitability
- ✅ Integration seamless and automated

**Data Flow Diagram**:
```
[Salla API] → [Webhook] → [OrderSyncService]
                             ↓
                       [UnifiedOrder created]
                             ↓
                       [OrderSynced event]
                             ↓
                  [UpdateOrderProfitability listener]
                             ↓
         ┌───────────────────┴───────────────────┐
         ↓                                       ↓
  [ProductCost (EPIC-002)]            [ChannelCost (EPIC-002)]
         ↓                                       ↓
         └───────────────────┬───────────────────┘
                             ↓
                [ProfitabilityCalculator]
                             ↓
                   [UnifiedOrderItem updated]
                             ↓
                   [Display in UI]
```

---

### Flow 3.2: Margin Protection Triggered by Cost Increase

**Actor**: System (Automated)
**Goal**: Automatically create margin protection event when costs increase
**Preconditions**: Pricing strategy exists, product costs updated

**Steps**:

1. **Cost Increase Detected**
   - Pricing Manager updates ProductCost
   - Product: iPhone 15 Pro
   - Old COGS: 750.00 SAR
   - New COGS: 850.00 SAR (+100.00)
   - Effective From: Today
   - Save

2. **Event: ProductCostUpdated**
   - Backend dispatches event:
     ```php
     event(new ProductCostUpdated(
       productId: 'iphone-15-pro',
       oldCost: 750.00,
       newCost: 850.00,
       delta: +100.00
     ));
     ```

3. **Listener: CheckMarginImpact**
   - Triggered by ProductCostUpdated
   - Retrieves active pricing strategies for product
   - Found strategy:
     - Product: iPhone 15 Pro
     - Channel: Salla Store
     - Current Price: 1059.99 SAR
     - Strategy: Cost-Plus with 25% markup

4. **Recalculate Break-Even**
   - Old break-even: 848.02 SAR (with 750 COGS)
   - New break-even: 948.02 SAR (with 850 COGS)
   - Current price: 1059.99 SAR
   - Old margin: (1059.99 - 848.02) / 1059.99 = 20%
   - New margin: (1059.99 - 948.02) / 1059.99 = 10.6%

5. **Margin Threshold Check**
   - Company policy: Minimum margin = 15%
   - New margin: 10.6% < 15% (BELOW THRESHOLD)
   - Action: Create margin protection event

6. **Auto-Create Margin Protection Event**
   - System creates event:
     - product_id: iPhone 15 Pro
     - channel_id: Salla Store
     - event_type: "cost_increase"
     - original_price: 1059.99 SAR
     - proposed_price: [calculated] 1093.02 SAR (to maintain 20% margin)
     - margin_impact: -9.4%
     - reason: "COGS increased from 750 to 850 SAR. Current price yields only 10.6% margin, below 15% threshold."
     - status: "blocked"
     - expires_at: +7 days

7. **Notification Sent**
   - Email to Pricing Manager:
     - Subject: "Margin Protection Required - iPhone 15 Pro"
     - Body: Details of cost increase and margin impact
     - Recommended action: Increase price to 1093.02 SAR
     - Link to approval page

8. **Pricing Manager Reviews**
   - Receives email
   - Navigates to **Pricing** > **Margin Protection**
   - Views auto-created event
   - Options:
     - **Option A**: Request approval to keep current price (accept lower margin)
     - **Option B**: Increase price to recommended 1093.02 SAR
     - **Option C**: Find cost reduction opportunities

9. **Decision: Increase Price**
   - Pricing Manager updates pricing strategy:
     - New price: 1093.02 SAR
     - Psychological pricing: 1092.99 SAR
   - Save
   - Margin protection event auto-closes (price increased)

10. **Price Synced to Channels**
    - New price pushed to Salla API
    - Product updated: 1092.99 SAR
    - Margin restored: ~20%

**Success Criteria**:
- ✅ Cost increase detected automatically
- ✅ Margin impact calculated
- ✅ Margin protection event created
- ✅ Pricing manager notified
- ✅ Price updated to maintain margin
- ✅ Integration between Pricing and Margin Protection seamless

**Alternative Flow (Approval Required)**:
- If Pricing Manager wants to keep 1059.99 SAR:
  - Submit margin protection approval request (Flow 1.5)
  - Admin reviews and approves/rejects
  - If approved: Price stays, margin accepted at 10.6%
  - If rejected: Price must be increased

---

## 4. Administrative Flows

### Flow 4.1: Setting Up Channel Integration

**Actor**: Admin
**Goal**: Configure new sales channel for order sync
**Preconditions**: Channel account exists (e.g., Salla store)

**Steps**:

1. **Navigate to Channels**
   - Login as Admin
   - Navigate to **Settings** > **Channels**
   - View channels list

2. **Create New Channel**
   - Click **"Add Channel"** button
   - Form fields:
     - **Name**: "Salla Store - Riyadh"
     - **Type**: Dropdown → Salla
     - **Code**: "salla-riyadh" (unique identifier)
     - **Description**: "Primary Salla store in Riyadh"
     - **Status**: Active

3. **Configure API Credentials**
   - Salla API section:
     - **API Key**: [Paste from Salla dashboard]
     - **API Secret**: [Paste from Salla dashboard]
     - **Store URL**: https://riyadh-store.salla.sa
     - **Webhook Secret**: [Auto-generated or paste]
   - Click **"Test Connection"**
   - Success: ✅ "Connected to Salla API successfully"

4. **Configure Order Sync Settings**
   - Sync configuration:
     - **Auto Sync**: Enabled
     - **Sync Frequency**: Hourly
     - **Last Sync**: Never
     - **Sync Mode**: Incremental
     - **Sync Orders From**: Last 30 days (initial)

5. **Save Channel**
   - Click **"Save Channel"**
   - Toast: "Channel created successfully"
   - Channel added to list

6. **Create Webhook in Salla Dashboard**
   - Open Salla admin panel
   - Navigate to **Settings** > **Webhooks**
   - Click **"Create Webhook"**
   - Webhook settings:
     - **Name**: UnoPim Integration
     - **Endpoint URL**: `https://unopim.local/api/v1/order/webhooks/receive/salla-riyadh`
     - **Events**: Select:
       - ✅ order.created
       - ✅ order.updated
       - ✅ order.cancelled
       - ✅ order.paid
     - **Secret**: [Copy from UnoPim channel config]
   - Save webhook

7. **Test Webhook**
   - In UnoPim, navigate to **Orders** > **Webhooks**
   - Find "Salla Store - Riyadh" webhook (auto-created)
   - Click **"Send Test Event"**
   - Select event: order.created
   - Send
   - View response: 200 OK
   - Success: ✅ Webhook working

8. **Initial Sync**
   - Navigate to **Orders** > **Sync**
   - Click **"Sync Now"** for Salla Store
   - Sync mode: Full (initial load)
   - Date range: Last 30 days
   - Start sync
   - Wait for completion (see Flow 2.4)
   - Result: 245 orders synced

**Success Criteria**:
- ✅ Channel configured with API credentials
- ✅ API connection tested successfully
- ✅ Webhook created in Salla
- ✅ Webhook tested and working
- ✅ Initial sync completed
- ✅ Orders flowing from Salla to UnoPim

---

### Flow 4.2: Configuring ACL Permissions

**Actor**: Admin
**Goal**: Set up role-based access control for pricing and orders
**Preconditions**: Roles exist (Admin, Order Manager, Pricing Manager, Warehouse, Viewer)

**Steps**:

1. **Navigate to Permissions**
   - Login as Admin
   - Navigate to **Settings** > **Access Control** > **Roles**

2. **Create "Order Manager" Role**
   - Click **"Create Role"**
   - Name: "Order Manager"
   - Description: "Can view and manage orders, sync channels, view profitability"
   - Save role

3. **Assign Order Permissions**
   - Select "Order Manager" role
   - Click **"Manage Permissions"**
   - Permission tree expanded:
     - **Order Management** (parent)
       - ✅ Orders
         - ✅ order.orders.view
         - ✅ order.orders.edit
         - ❌ order.orders.delete (admin only)
         - ✅ order.orders.mass-update
         - ✅ order.orders.export
       - ✅ Order Sync
         - ✅ order.sync.view
         - ✅ order.sync.manual-sync
         - ✅ order.sync.retry
         - ❌ order.sync.settings (admin only)
       - ✅ Profitability
         - ✅ order.profitability.view
         - ✅ order.profitability.export
       - ❌ Webhooks
         - ❌ order.webhooks.* (admin only)
   - Save permissions

4. **Create "Pricing Manager" Role**
   - Create role: "Pricing Manager"
   - Permissions:
     - **Pricing Management**
       - ✅ Product Costs
         - ✅ pricing.costs.view
         - ✅ pricing.costs.create
         - ✅ pricing.costs.edit
         - ❌ pricing.costs.delete
       - ✅ Channel Costs
         - ✅ pricing.channel-costs.*
       - ✅ Pricing Strategies
         - ✅ pricing.strategies.*
       - ✅ Break-Even Calculator
         - ✅ pricing.break-even.calculate
       - ✅ Margin Protection
         - ✅ pricing.margin-protection.request
         - ❌ pricing.margin-protection.approve (admin only)

5. **Create "Warehouse Staff" Role**
   - Create role: "Warehouse Staff"
   - Limited permissions:
     - ✅ order.orders.view
     - ✅ order.orders.edit (only status, tracking)
     - ❌ All other permissions

6. **Assign Users to Roles**
   - Navigate to **Users**
   - User: "order.manager@unopim.local"
     - Assign role: Order Manager
   - User: "pricing.manager@unopim.local"
     - Assign role: Pricing Manager
   - User: "warehouse.staff@unopim.local"
     - Assign role: Warehouse Staff

7. **Test Permissions**
   - **Test 1**: Login as Order Manager
     - ✅ Can view orders
     - ✅ Can edit order status
     - ❌ Cannot delete orders (403)
     - ✅ Can trigger manual sync
     - ❌ Cannot manage webhooks (403)
   - **Test 2**: Login as Pricing Manager
     - ✅ Can create product costs
     - ✅ Can calculate break-even
     - ✅ Can request margin protection approval
     - ❌ Cannot approve margin protection (403)
   - **Test 3**: Login as Warehouse Staff
     - ✅ Can view orders
     - ✅ Can edit tracking number
     - ❌ Cannot edit customer info (read-only)
     - ❌ Cannot access profitability (403)

**Success Criteria**:
- ✅ All roles created with appropriate permissions
- ✅ Users assigned to correct roles
- ✅ Permissions enforced (403 errors for unauthorized)
- ✅ Principle of least privilege applied
- ✅ Audit trail of permission changes

---

## Summary of User Flows

| Flow ID | Flow Name | Primary Actor | Key Package(s) | Integration Points |
|---------|-----------|---------------|----------------|-------------------|
| 1.1 | Setting Up Product Costs | Pricing Manager | Pricing | - |
| 1.2 | Configuring Channel Costs | Channel Manager | Pricing | - |
| 1.3 | Calculating Break-Even Price | Pricing Manager | Pricing | ProductCost + ChannelCost |
| 1.4 | Creating Pricing Strategy | Pricing Manager | Pricing | Break-Even Calculator |
| 1.5 | Margin Protection Approval | Pricing Mgr + Admin | Pricing | Email notifications |
| 2.1 | Viewing Multi-Channel Orders | Order Manager | Order | - |
| 2.2 | Viewing Order Details | Order Manager | Order | Profitability display |
| 2.3 | Editing Order Status | Warehouse Staff | Order | Event system |
| 2.4 | Manual Order Sync | Order Manager | Order | Channel API, Events |
| 2.5 | Analyzing Profitability | Order Manager | Order | Chart.js, Export |
| 2.6 | Comparing Channels | Order Manager | Order | Profitability metrics |
| 3.1 | **Order Profitability Integration** | System | **Both** | **ProductCost → Order Items** |
| 3.2 | **Margin Protection Auto-Trigger** | System | **Both** | **Cost Update → Margin Event** |
| 4.1 | Channel Integration Setup | Admin | Order | Webhook configuration |
| 4.2 | ACL Permission Configuration | Admin | Both | RBAC enforcement |

---

## Testing Recommendations

### Manual Testing Priority

**High Priority** (Critical Paths):
1. Flow 3.1 - Order Profitability Integration (End-to-end EPIC-002 + EPIC-006)
2. Flow 1.3 - Break-Even Calculation (Core pricing logic)
3. Flow 2.4 - Order Sync (Multi-channel integration)
4. Flow 1.5 - Margin Protection Workflow (Approval process)

**Medium Priority** (Important Features):
5. Flow 2.5 - Profitability Dashboard (Business intelligence)
6. Flow 1.4 - Pricing Strategy with Psychological Pricing
7. Flow 2.3 - Order Status Updates (Daily operations)
8. Flow 4.2 - ACL Permissions (Security)

**Low Priority** (Edge Cases):
9. Flow 1.2 - Tiered Channel Costs
10. Flow 3.2 - Auto-triggered Margin Protection

### Automated Testing Coverage

- **Unit Tests**: All calculations (break-even, profitability, rounding)
- **Feature Tests**: CRUD operations, API endpoints, permissions
- **Integration Tests**: Pricing → Order profitability flow
- **E2E Tests**: Full user flows (Playwright)

---

## Appendix: Data Flow Summary

```
┌─────────────────────────────────────────────────────────────┐
│                   EPIC-002: Pricing Package                  │
├─────────────────────────────────────────────────────────────┤
│  ProductCost → ChannelCost → Break-Even → Strategy          │
│       ↓              ↓            ↓            ↓             │
│   Fixed Costs    Variable %   Min Price   Final Price       │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          │ Integration Point
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    EPIC-006: Order Package                   │
├─────────────────────────────────────────────────────────────┤
│  Channel Sync → UnifiedOrder → Items → Profitability        │
│       ↑              ↓           ↓            ↓              │
│   Webhook API    Order Data   Cost Basis   Profit/Margin    │
└─────────────────────────────────────────────────────────────┘

Key Integration: OrderItem.cost_basis ← ProductCost.amount
                 OrderItem.profit_amount ← (price - cost_basis) × qty
```

---

**End of User Flows Documentation**
