# Pricing Package - Manual Test Cases

## Document Information

| Field        | Value                                          |
| ------------ | ---------------------------------------------- |
| Module       | Product Information Management                 |
| Feature      | Pricing & Cost Management                      |
| Date         | 2026-02-18                                     |
| Version      | 1.0                                            |
| Package      | Webkul/Pricing                                 |
| Frontend URL | `http://localhost:8000/admin/pricing`          |
| API Base     | `/api/v1/pricing`                              |
| Collections  | `product_costs`, `channel_costs`, `margin_protection_events`, `pricing_strategies` |

---

## Test Credentials

| Role           | Username          | Email                            | Password    | Tenant      |
| -------------- | ----------------- | -------------------------------- | ----------- | ----------- |
| **Admin**      | `admin`           | `admin@unopim.local`             | `Admin@123` | `default`   |
| **Channel Mgr**| `channel.manager` | `channel.manager@unopim.local`   | `Admin@123` | `default`   |
| **Pricing Mgr**| `pricing.manager` | `pricing.manager@unopim.local`   | `Admin@123` | `default`   |
| **Read Only**  | `viewer`          | `viewer@unopim.local`            | `Admin@123` | `default`   |

---

## 1. Product Cost Management

### 1.1 Create Product Cost

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **COST-CR-001** | Create Basic COGS                       | **Pricing Mgr** | Product exists          | 1. Navigate to **Pricing** > **Product Costs**.<br>2. Click **"Create Cost"**.<br>3. Select Product: "iPhone 15 Pro".<br>4. Cost Type: "COGS".<br>5. Amount: 750.00.<br>6. Currency: USD.<br>7. Effective From: Today.<br>8. Click **"Save"**. | - Cost created successfully.<br>- Status: Active.<br>- Toast: "Product cost created successfully".              |
| **COST-CR-002** | Create Cost with Date Range             | **Pricing Mgr** | Product exists          | 1. Add Product Cost:<br>- Product: "MacBook Pro"<br>- Cost Type: "Operational"<br>- Amount: 100.00<br>- Effective From: 2026-01-01<br>- Effective To: 2026-12-31                                                                              | - Date range cost created.<br>- Effective for specified period only.                                            |
| **COST-CR-003** | Create Multi-Currency Cost              | **Pricing Mgr** | Multi-currency enabled  | 1. Create costs in USD, EUR, SAR.<br>2. Same product, same cost type.                                                                                                                                                                         | - Each currency stored separately.<br>- Currency-specific retrieval works.                                      |
| **COST-CR-004** | Create Cost - Missing Product           | **Pricing Mgr** | Logged in               | 1. Leave product_id empty.                                                                                                                                                                                                                     | - Error: "The product id field is required".                                                                    |
| **COST-CR-005** | Create Cost - Missing Cost Type         | **Pricing Mgr** | Logged in               | 1. Leave cost_type empty.                                                                                                                                                                                                                      | - Error: "The cost type field is required".                                                                     |
| **COST-CR-006** | Create Cost - Missing Amount            | **Pricing Mgr** | Logged in               | 1. Leave amount empty.                                                                                                                                                                                                                         | - Error: "The amount field is required".                                                                        |
| **COST-CR-007** | Create Cost - Negative Amount           | **Pricing Mgr** | Logged in               | 1. Amount: -100.00                                                                                                                                                                                                                             | - Error: "The amount must be at least 0".                                                                       |
| **COST-CR-008** | Create Cost - Invalid Date Range        | **Pricing Mgr** | Logged in               | 1. Effective From: 2026-12-31<br>2. Effective To: 2026-01-01                                                                                                                                                                                  | - Error: "effective_to must be after or equal to effective_from".                                               |
| **COST-CR-009** | Create Cost - Invalid Product ID        | **Pricing Mgr** | Logged in               | 1. Product ID: "invalid-id"                                                                                                                                                                                                                    | - Error: "The selected product id is invalid".                                                                  |
| **COST-CR-010** | Create Cost - All Cost Types            | **Pricing Mgr** | Product exists          | 1. Create costs for all types:<br>- COGS<br>- Operational<br>- Shipping<br>- Overhead<br>- Marketing                                                                                                                                          | - All cost types accepted.<br>- Stored separately.                                                              |

### 1.2 Read Product Costs

| ID             | Title                  | Role           | Pre-conditions      | Detailed Test Steps                 | Expected Result                                       |
|----------------|------------------------|----------------|---------------------|-------------------------------------|-------------------------------------------------------|
| **COST-RD-001** | List All Product Costs | **Admin**      | Costs exist         | 1. GET `/admin/pricing/costs`.      | - All costs visible.<br>- Paginated (20 per page).    |
| **COST-RD-002** | Filter by Product      | **Pricing Mgr** | Multiple costs      | 1. Filter by Product: "iPhone 15".  | - Only costs for that product.                        |
| **COST-RD-003** | Filter by Cost Type    | **Pricing Mgr** | Various types       | 1. Filter: Cost Type = "COGS".      | - Only COGS costs shown.                              |
| **COST-RD-004** | Filter by Currency     | **Pricing Mgr** | Multi-currency      | 1. Filter: Currency = "EUR".        | - Only EUR costs.                                     |
| **COST-RD-005** | Filter by Date Range   | **Pricing Mgr** | Historical costs    | 1. Filter: Effective From between dates. | - Costs within date range.                       |
| **COST-RD-006** | View Cost Details      | **Pricing Mgr** | Cost exists         | 1. Click cost row to view details.  | - Full cost information.<br>- Product details populated. |
| **COST-RD-007** | API: Get Cost by ID    | **API**        | Cost exists         | 1. GET `/api/v1/pricing/costs/{id}`.| - Cost details in JSON.<br>- Product relationship loaded. |

### 1.3 Update Product Cost

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **COST-UP-001** | Update Cost Amount         | **Pricing Mgr** | Cost active            | 1. Edit cost.<br>2. Change amount: 800.00.                 | - Amount updated.<br>- Updated timestamp set.                  |
| **COST-UP-002** | Update Effective Dates     | **Pricing Mgr** | Cost active            | 1. Extend Effective To: +6 months.                         | - Date updated.<br>- Validation: from < to.                    |
| **COST-UP-003** | Update Cost Type           | **Pricing Mgr** | Cost active            | 1. Change type: COGS → Operational.                        | - Type updated.                                                |
| **COST-UP-004** | Update - Invalid Amount    | **Pricing Mgr** | Cost exists            | 1. Set amount: -50.00.                                     | - Error: "Amount must be positive".                            |
| **COST-UP-005** | Update - Invalid Dates     | **Pricing Mgr** | Cost exists            | 1. Effective To before Effective From.                     | - Error: "effective_to must be after effective_from".          |
| **COST-UP-006** | Update - Change Currency   | **Pricing Mgr** | Cost exists            | 1. Change currency: USD → EUR.                             | - Warning: Creates new cost entry (currency immutable).        |
| **COST-UP-007** | Update Historical Cost     | **Pricing Mgr** | Past effective date    | 1. Try to update old cost.                                 | - **Option A**: Allowed (audit trail).<br>- **Option B**: Warning shown. |
| **COST-UP-008** | Update - Unauthorized      | **Read Only**  | Cost exists            | 1. Try to update cost.                                     | - 403 Forbidden.<br>- Permission required: `pricing.costs.edit`. |

### 1.4 Delete Product Cost

| ID             | Title                 | Role           | Pre-conditions     | Detailed Test Steps           | Expected Result                                                                                     |
|----------------|-----------------------|----------------|--------------------|-------------------------------|-----------------------------------------------------------------------------------------------------|
| **COST-DL-001** | Delete Cost - Admin   | **Admin**      | Cost exists        | 1. Delete cost.<br>2. Confirm. | - Cost removed.<br>- Toast: "Cost deleted successfully".                                            |
| **COST-DL-002** | Delete - Unauthorized | **Read Only**  | Cost exists        | 1. Try to delete cost.         | - 403 Forbidden.                                                                                    |
| **COST-DL-003** | Delete Active Cost    | **Pricing Mgr** | Cost in use        | 1. Delete cost used in calculations. | - **Option A**: Prevented (soft delete).<br>- **Option B**: Warning about impact.              |

---

## 2. Channel Cost Management

### 2.1 Create Channel Cost

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **CHAN-CR-001** | Create Basic Channel Cost               | **Channel Mgr** | Channel exists          | 1. Navigate to **Pricing** > **Channel Costs**.<br>2. Click **"Create"**.<br>3. Select Channel: "Salla Store".<br>4. Fee Type: "Commission".<br>5. Fee Structure: "Percentage".<br>6. Value: 2.5.<br>7. Click **"Save"**.                    | - Channel cost created.<br>- Toast: "Channel cost created successfully".                                        |
| **CHAN-CR-002** | Create Fixed Fee Channel Cost           | **Channel Mgr** | Channel exists          | 1. Fee Structure: "Fixed Amount".<br>2. Value: 5.00.<br>3. Currency: USD.                                                                                                                                                                     | - Fixed fee stored.<br>- Applied per transaction.                                                               |
| **CHAN-CR-003** | Create Tiered Channel Cost              | **Channel Mgr** | Channel exists          | 1. Fee Structure: "Tiered".<br>2. Add tiers:<br>- Tier 1: 0-1000 = 3%<br>- Tier 2: 1001-5000 = 2.5%<br>- Tier 3: 5001+ = 2%                                                                                                                   | - Tiered structure stored.<br>- Calculation logic uses correct tier.                                            |
| **CHAN-CR-004** | Create Cost - Missing Channel           | **Channel Mgr** | Logged in               | 1. Leave channel_id empty.                                                                                                                                                                                                                     | - Error: "The channel id field is required".                                                                    |
| **CHAN-CR-005** | Create Cost - Missing Fee Type          | **Channel Mgr** | Logged in               | 1. Leave fee_type empty.                                                                                                                                                                                                                       | - Error: "The fee type field is required".                                                                      |
| **CHAN-CR-006** | Create Cost - Invalid Fee Value         | **Channel Mgr** | Logged in               | 1. Percentage: 150% (>100).                                                                                                                                                                                                                    | - Error: "Percentage must be between 0 and 100".                                                                |
| **CHAN-CR-007** | Create Cost - All Fee Types             | **Channel Mgr** | Channel exists          | 1. Create costs for types:<br>- Commission<br>- Payment Processing<br>- Subscription<br>- Listing Fee<br>- Transaction Fee                                                                                                                     | - All fee types accepted.                                                                                       |

### 2.2 Break-Even Calculation

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **BREAK-001**  | Calculate Break-Even Price              | **Pricing Mgr** | Product with costs      | 1. Navigate to **Pricing** > **Break-Even Calculator**.<br>2. Select Product: "iPhone 15".<br>3. Select Channel: "Salla".<br>4. Click **"Calculate"**.                                                                                       | - Break-even price displayed.<br>- Formula: `breakEven = fixedCosts / (1 - variableRate)`.                      |
| **BREAK-002**  | Break-Even with Only Fixed Costs        | **Pricing Mgr** | COGS only               | 1. Product Cost: COGS = 750.<br>2. No channel costs.                                                                                                                                                                                          | - Break-even = 750.<br>- No margin added.                                                                       |
| **BREAK-003**  | Break-Even with Channel Commission      | **Pricing Mgr** | Costs + channel fee     | 1. COGS: 750.<br>2. Channel: 2.5% commission.<br>3. Calculate.                                                                                                                                                                                | - Break-even = 750 / (1 - 0.025) = 769.23.                                                                      |
| **BREAK-004**  | Break-Even with Multiple Costs          | **Pricing Mgr** | Multiple cost types     | 1. COGS: 750.<br>2. Shipping: 50.<br>3. Marketing: 25.<br>4. Channel: 2.5%.                                                                                                                                                                   | - Total fixed: 825.<br>- Break-even = 825 / (1 - 0.025) = 846.15.                                               |
| **BREAK-005**  | Break-Even - Division by Zero           | **Pricing Mgr** | Variable rate = 100%    | 1. Channel fee: 100%.                                                                                                                                                                                                                          | - Error: "Break-even impossible with 100% variable rate".                                                       |
| **BREAK-006**  | Break-Even - Variable Rate > 100%       | **Pricing Mgr** | Invalid setup           | 1. Variable costs > 100%.                                                                                                                                                                                                                      | - Error: "Total variable costs cannot exceed 100%".                                                             |
| **BREAK-007**  | Break-Even API Endpoint                 | **API**        | Product with costs      | 1. POST `/api/v1/pricing/break-even`.<br>2. Body: `{product_id, channel_id}`.                                                                                                                                                                 | - JSON response with break-even price and breakdown.                                                            |

---

## 3. Margin Protection Management

### 3.1 Create Margin Protection Event

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **MARGIN-CR-001** | Create Margin Protection Request       | **Pricing Mgr** | Product exists          | 1. Navigate to **Pricing** > **Margin Protection**.<br>2. Click **"Request Approval"**.<br>3. Product: "iPhone 15".<br>4. Original Price: 999.00.<br>5. Proposed Price: 899.00.<br>6. Reason: "Competitor pricing pressure".<br>7. Submit.   | - Event created with status "Blocked".<br>- Awaiting approval workflow.                                         |
| **MARGIN-CR-002** | Margin Below Threshold Triggers Event   | **System**     | Product with cost       | 1. Update pricing strategy.<br>2. Proposed margin < minimum threshold.                                                                                                                                                                        | - Auto-create margin protection event.<br>- Status: Blocked.                                                    |
| **MARGIN-CR-003** | Approve Margin Protection               | **Admin**      | Event blocked           | 1. View pending approvals.<br>2. Select event.<br>3. Click **"Approve"**.<br>4. Add approval note.                                                                                                                                            | - Status → Approved.<br>- Price change allowed.<br>- approved_by and approved_at set.                           |
| **MARGIN-CR-004** | Reject Margin Protection                | **Admin**      | Event blocked           | 1. View pending approvals.<br>2. Select event.<br>3. Click **"Reject"**.<br>4. Add rejection reason.                                                                                                                                          | - Status → Rejected.<br>- Price change prevented.<br>- rejected_by and rejected_at set.                         |
| **MARGIN-CR-005** | Event with Expiration                   | **Pricing Mgr** | Creating event          | 1. Set expiration: +7 days.                                                                                                                                                                                                                    | - expires_at timestamp set.<br>- Auto-reject after expiration.                                                  |
| **MARGIN-CR-006** | Expired Event Auto-Reject               | **System**     | Event past expiration   | 1. Event expires_at < now.<br>2. Status still "Blocked".                                                                                                                                                                                       | - Auto-transition to "Rejected".<br>- System job handles expiration.                                            |
| **MARGIN-CR-007** | Event - Missing Product                 | **Pricing Mgr** | Logged in               | 1. Leave product_id empty.                                                                                                                                                                                                                     | - Error: "The product id field is required".                                                                    |
| **MARGIN-CR-008** | Event - Missing Reason                  | **Pricing Mgr** | Logged in               | 1. Leave reason empty.                                                                                                                                                                                                                         | - Error: "The reason field is required".                                                                        |
| **MARGIN-CR-009** | Event - Invalid Event Type              | **Pricing Mgr** | Creating event          | 1. event_type: "invalid".                                                                                                                                                                                                                      | - Enum validation.<br>- Valid: margin_below_threshold, cost_increase, competitive_pricing.                      |

### 3.2 Margin Protection Workflow

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **MARGIN-WF-001** | Blocked → Approved Flow   | **Admin**      | Event blocked          | 1. Approve event.                                          | - Status: Blocked → Approved.<br>- Price update allowed.       |
| **MARGIN-WF-002** | Blocked → Rejected Flow   | **Admin**      | Event blocked          | 1. Reject event.                                           | - Status: Blocked → Rejected.<br>- Price kept at original.     |
| **MARGIN-WF-003** | Cannot Reverse Approval   | **Admin**      | Event approved         | 1. Try to change back to Blocked.                          | - Error: "Cannot reverse approval".                            |
| **MARGIN-WF-004** | Approval Notification     | **System**     | Event approved         | 1. Approve event.                                          | - Email notification to requester.<br>- Event log created.     |
| **MARGIN-WF-005** | Rejection Notification    | **System**     | Event rejected         | 1. Reject event.                                           | - Email notification to requester with reason.                 |

---

## 4. Pricing Strategy Management

### 4.1 Create Pricing Strategy

| ID             | Title                                    | Role           | Pre-conditions           | Detailed Test Steps                                                                                                                                                                                                                         | Expected Result                                                                                                 |
|----------------|------------------------------------------|----------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **STRAT-CR-001** | Create Cost-Plus Strategy              | **Pricing Mgr** | Product with costs      | 1. Navigate to **Pricing** > **Strategies**.<br>2. Click **"Create"**.<br>3. Product: "MacBook Pro".<br>4. Channel: "Shopify".<br>5. Strategy Type: "Cost-Plus".<br>6. Markup: 30%.<br>7. Click **"Save"**.                                  | - Strategy created.<br>- Final price = cost × 1.30.                                                             |
| **STRAT-CR-002** | Create Competitive Strategy             | **Pricing Mgr** | Product exists          | 1. Strategy Type: "Competitive".<br>2. Reference Price: 1099.00.<br>3. Position: "Match" / "Below" / "Above".                                                                                                                                 | - Strategy created.<br>- Price adjusted based on competitor.                                                    |
| **STRAT-CR-003** | Create Value-Based Strategy             | **Pricing Mgr** | Product exists          | 1. Strategy Type: "Value-Based".<br>2. Customer Segments defined.<br>3. Value drivers identified.                                                                                                                                             | - Strategy created.<br>- Price based on perceived value.                                                        |
| **STRAT-CR-004** | Create Dynamic Strategy                 | **Pricing Mgr** | Product exists          | 1. Strategy Type: "Dynamic".<br>2. Rules: Demand-based, Time-based, Inventory-based.                                                                                                                                                          | - Strategy created.<br>- Price adjusts automatically.                                                           |
| **STRAT-CR-005** | Apply Psychological Pricing             | **Pricing Mgr** | Strategy exists         | 1. Enable psychological pricing.<br>2. Rounding: ".99".<br>3. Base price: 1000.00.                                                                                                                                                            | - Final price: 999.99.<br>- Rounding applied correctly.                                                         |
| **STRAT-CR-006** | Psychological - All Rounding Options    | **Pricing Mgr** | Strategy exists         | 1. Test roundings:<br>- ".99" → 999.99<br>- ".95" → 999.95<br>- ".00" → 1000.00<br>- "none" → 1000.23                                                                                                                                         | - Each rounding works correctly.                                                                                |
| **STRAT-CR-007** | Strategy - Missing Product              | **Pricing Mgr** | Logged in               | 1. Leave product_id empty.                                                                                                                                                                                                                     | - Error: "The product id field is required".                                                                    |
| **STRAT-CR-008** | Strategy - Invalid Strategy Type        | **Pricing Mgr** | Creating strategy       | 1. strategy_type: "invalid".                                                                                                                                                                                                                   | - Enum validation.<br>- Valid: cost_plus, competitive, value_based, dynamic.                                    |
| **STRAT-CR-009** | Strategy - Multiple Per Product-Channel | **Pricing Mgr** | Strategy exists         | 1. Create second strategy for same product-channel.                                                                                                                                                                                            | - **Option A**: Replace existing.<br>- **Option B**: Unique constraint violation.                               |

### 4.2 Psychological Pricing Tests

| ID             | Title                       | Role           | Pre-conditions         | Detailed Test Steps                                        | Expected Result                                                |
|----------------|-----------------------------|----------------|------------------------|------------------------------------------------------------|----------------------------------------------------------------|
| **PSYCH-001**  | Round to .99               | **Pricing Mgr** | Base price set         | 1. Base: 1234.56.<br>2. Rounding: ".99".                   | - Final: 1234.99.                                              |
| **PSYCH-002**  | Round to .95               | **Pricing Mgr** | Base price set         | 1. Base: 1234.56.<br>2. Rounding: ".95".                   | - Final: 1234.95.                                              |
| **PSYCH-003**  | Round to .00               | **Pricing Mgr** | Base price set         | 1. Base: 1234.56.<br>2. Rounding: ".00".                   | - Final: 1235.00 (rounded up).                                 |
| **PSYCH-004**  | No Rounding                | **Pricing Mgr** | Base price set         | 1. Base: 1234.56.<br>2. Rounding: "none".                  | - Final: 1234.56 (unchanged).                                  |
| **PSYCH-005**  | Edge Case: Already .99     | **Pricing Mgr** | Base: 999.99           | 1. Rounding: ".99".                                        | - Final: 999.99 (no change).                                   |
| **PSYCH-006**  | Edge Case: Very Low Price  | **Pricing Mgr** | Base: 5.50             | 1. Rounding: ".99".                                        | - Final: 5.99.                                                 |

---

## 5. Validation Tests

| ID              | Title                       | Role           | Pre-conditions    | Detailed Test Steps          | Expected Result                                        |
|-----------------|-----------------------------|----------------|-------------------|------------------------------|--------------------------------------------------------|
| **VAL-001**     | Product ID - Required       | **Pricing Mgr** | Creating cost     | 1. Leave product_id empty.   | - Error: "The product id field is required".           |
| **VAL-002**     | Amount - Required           | **Pricing Mgr** | Creating cost     | 1. Leave amount empty.       | - Error: "The amount field is required".               |
| **VAL-003**     | Amount - Positive Only      | **Pricing Mgr** | Creating cost     | 1. Amount: -100.             | - Error: "The amount must be at least 0".              |
| **VAL-004**     | Currency - Valid Code       | **Pricing Mgr** | Creating cost     | 1. Currency: "INVALID".      | - Error: "Invalid currency code".                      |
| **VAL-005**     | Date Range - From < To      | **Pricing Mgr** | Creating cost     | 1. effective_to < effective_from. | - Error: "effective_to must be after effective_from". |
| **VAL-006**     | Percentage - 0-100 Range    | **Channel Mgr** | Creating channel cost | 1. Fee: 150%.           | - Error: "Percentage must be between 0 and 100".       |
| **VAL-007**     | Enum - Cost Type            | **Pricing Mgr** | Creating cost     | 1. cost_type: "invalid".     | - Valid: cogs, operational, shipping, overhead, marketing. |
| **VAL-008**     | Enum - Fee Type             | **Channel Mgr** | Creating channel cost | 1. fee_type: "invalid". | - Valid: commission, payment_processing, subscription, listing_fee, transaction_fee. |
| **VAL-009**     | Enum - Event Type           | **Pricing Mgr** | Creating margin event | 1. event_type: "invalid". | - Valid: margin_below_threshold, cost_increase, competitive_pricing. |
| **VAL-010**     | Enum - Strategy Type        | **Pricing Mgr** | Creating strategy | 1. strategy_type: "invalid". | - Valid: cost_plus, competitive, value_based, dynamic. |

---

## 6. Permission & RBAC Tests

| ID               | Title                  | Role           | Pre-conditions      | Detailed Test Steps             | Expected Result                            |
|------------------|------------------------|----------------|---------------------|---------------------------------|--------------------------------------------|
| **PERM-001**     | Admin Full Access      | **Admin**      | Logged in           | 1. Access all pricing features. | - All CRUD operations allowed.             |
| **PERM-002**     | Pricing Mgr - Create Costs | **Pricing Mgr** | Logged in       | 1. Create product cost.         | - Allowed with `pricing.costs.create`.     |
| **PERM-003**     | Pricing Mgr - Edit Costs | **Pricing Mgr** | Cost exists       | 1. Update cost.                 | - Allowed with `pricing.costs.edit`.       |
| **PERM-004**     | Channel Mgr - Channel Costs | **Channel Mgr** | Logged in       | 1. Create channel cost.         | - Allowed with `pricing.channel-costs.create`. |
| **PERM-005**     | Read Only - View Only  | **Read Only**  | Logged in           | 1. View pricing data.           | - View allowed.<br>- Edit/Delete: 403.     |
| **PERM-006**     | Unauthorized - Create  | **Read Only**  | Logged in           | 1. Try to create cost.          | - 403 Forbidden.                           |
| **PERM-007**     | Unauthorized - Edit    | **Read Only**  | Cost exists         | 1. Try to update cost.          | - 403 Forbidden.                           |
| **PERM-008**     | Unauthorized - Delete  | **Read Only**  | Cost exists         | 1. Try to delete cost.          | - 403 Forbidden.                           |
| **PERM-009**     | Approve Margin Event   | **Admin**      | Event blocked       | 1. Approve event.               | - Allowed with `pricing.margin-protection.approve`. |
| **PERM-010**     | Non-Admin Cannot Approve | **Pricing Mgr** | Event blocked     | 1. Try to approve.              | - 403 Forbidden.                           |

---

## 7. Multi-Tenancy & Company Isolation

| ID              | Title                           | Role           | Pre-conditions | Detailed Test Steps                        | Expected Result           |
|-----------------|---------------------------------|----------------|----------------|--------------------------------------------|---------------------------|
| **TENANT-001**  | Cross-Tenant Access             | **Admin**      | Tenant A       | 1. Try to access Tenant B costs.           | - 404 Not Found.          |
| **TENANT-002**  | Tenant Isolation - Costs        | **Pricing Mgr** | Tenant A      | 1. List costs.                             | - Only Tenant A costs.    |
| **TENANT-003**  | Tenant ID Auto-Assigned         | **Pricing Mgr** | Creating cost  | 1. Create cost.<br>2. Check tenant_id.     | - Auto-set from context.  |
| **TENANT-004**  | Product from Different Tenant   | **Pricing Mgr** | Tenant A       | 1. Select Tenant B product.                | - Error: Product not found (tenant filter). |

---

## 8. Edge Cases & Hidden Scenarios

| ID               | Title                           | Role           | Pre-conditions            | Detailed Test Steps                             | Expected Result                                                                                      |
|------------------|---------------------------------|----------------|---------------------------|-------------------------------------------------|------------------------------------------------------------------------------------------------------|
| **EDGE-001**     | Zero Cost Amount                | **Pricing Mgr** | Creating cost             | 1. Amount: 0.00.                                | - Accepted (free product).                                                                           |
| **EDGE-002**     | Very Large Cost                 | **Pricing Mgr** | Creating cost             | 1. Amount: 999,999,999.99.                      | - Accepted (DECIMAL 15,4).                                                                           |
| **EDGE-003**     | Multiple Costs Same Date        | **Pricing Mgr** | Product with cost         | 1. Create second cost, same effective_from.     | - Both stored.<br>- Calculation uses latest by ID or specific logic.                                 |
| **EDGE-004**     | Overlapping Date Ranges         | **Pricing Mgr** | Cost with date range      | 1. Create overlapping cost periods.             | - **Option A**: Prevented.<br>- **Option B**: Allowed with logic to select active cost.              |
| **EDGE-005**     | Historical Cost Changes         | **Pricing Mgr** | Old cost                  | 1. Update cost from 2025.                       | - **Option A**: Allowed (audit).<br>- **Option B**: Warning about historical changes.                |
| **EDGE-006**     | Product Deleted                 | **Pricing Mgr** | Cost exists               | 1. Delete product.<br>2. Check cost.            | - Cost remains.<br>- Foreign key relationship (cascade or nullify).                                  |
| **EDGE-007**     | Channel Deleted                 | **Channel Mgr** | Channel cost exists       | 1. Delete channel.<br>2. Check channel cost.    | - Cost remains or cascades (depends on FK setup).                                                    |
| **EDGE-008**     | Unicode in Reason               | **Pricing Mgr** | Creating margin event     | 1. Reason: "تخفيض الأسعار 📉".                   | - Unicode/emoji stored correctly.                                                                    |
| **EDGE-009**     | Break-Even with Zero Costs      | **Pricing Mgr** | No costs                  | 1. Calculate break-even.                        | - Break-even = 0 or error message.                                                                   |
| **EDGE-010**     | Strategy Without Costs          | **Pricing Mgr** | No product costs          | 1. Create pricing strategy.                     | - **Option A**: Warning (missing cost data).<br>- **Option B**: Allowed (uses 0 as base).            |
| **EDGE-011**     | Multi-Currency Cost Retrieval   | **Pricing Mgr** | Costs in USD, EUR, SAR    | 1. Calculate price in EUR.                      | - Uses EUR cost.<br>- Fallback to default if EUR missing.                                            |
| **EDGE-012**     | Decimal Precision               | **Pricing Mgr** | Creating cost             | 1. Amount: 123.4567 (>4 decimals).              | - Rounded or truncated to 4 decimals (DECIMAL 15,4).                                                 |

---

## 9. API Contract & Pagination Tests

| ID              | Title              | Role      | Pre-conditions | Detailed Test Steps                   | Expected Result                                      |
|-----------------|--------------------|-----------|----------------|---------------------------------------|------------------------------------------------------|
| **API-001**     | Default Pagination | **Admin** | 50+ costs      | 1. GET `/api/v1/pricing/costs`.       | - 20 items per page.<br>- Pagination meta included.  |
| **API-002**     | Custom Limit       | **Admin** | Many costs     | 1. GET `?limit=50`.                   | - 50 items returned.<br>- Max 100 enforced.          |
| **API-003**     | Combined Filters   | **Admin** | Various data   | 1. GET `?product_id=x&cost_type=cogs`.| - Both filters applied.                              |
| **API-004**     | Response Structure | **Admin** | Costs exist    | 1. GET costs list.                    | - `{success: true, data: [...], meta: {...}}`        |
| **API-005**     | Product Populated  | **Admin** | Cost exists    | 1. GET cost by ID.                    | - Product details populated in response.             |
| **API-006**     | Break-Even Endpoint | **API**  | Product with costs | 1. POST `/api/v1/pricing/break-even`. | - JSON: `{breakEvenPrice, breakdown, formula}`.      |
| **API-007**     | Strategy Calculation | **API** | Strategy exists | 1. POST `/api/v1/pricing/calculate`.  | - JSON: `{finalPrice, basePrice, adjustments}`.      |

---

## 10. Integration Tests

| ID              | Title                           | Role           | Pre-conditions            | Detailed Test Steps                             | Expected Result                                                                                      |
|-----------------|---------------------------------|----------------|---------------------------|-------------------------------------------------|------------------------------------------------------------------------------------------------------|
| **INTEG-001**   | Cost → Break-Even → Strategy    | **Pricing Mgr** | Product exists            | 1. Create costs.<br>2. Calculate break-even.<br>3. Apply strategy.<br>4. Get final price. | - End-to-end pricing flow works.<br>- Final price reflects all layers.                              |
| **INTEG-002**   | Margin Protection → Approval → Price Update | **Admin** | Event blocked     | 1. Approve margin event.<br>2. Update pricing strategy. | - Approved event allows price change.<br>- Price updated successfully.                               |
| **INTEG-003**   | Multi-Channel Pricing           | **Pricing Mgr** | Multiple channels         | 1. Set different strategies per channel.        | - Each channel has correct price.<br>- Channel costs applied correctly.                              |
| **INTEG-004**   | Cost Change → Auto Re-Calculate | **System**     | Strategy exists           | 1. Update product cost.<br>2. Strategy recalculates. | - Price auto-updated based on new cost.                                                              |

---

## Verification Checklist

### Product Cost
- [ ] **CRUD**: All operations work for product costs
- [ ] **Validation**: product_id, cost_type, amount, currency_code required
- [ ] **Validation**: amount >= 0
- [ ] **Validation**: effective_to > effective_from (if provided)
- [ ] **Cost Types**: COGS, operational, shipping, overhead, marketing
- [ ] **Multi-Currency**: Costs stored per currency
- [ ] **Date Ranges**: Effective from/to dates work
- [ ] **Tenant Isolation**: Costs scoped by tenant_id

### Channel Cost
- [ ] **CRUD**: All operations work for channel costs
- [ ] **Fee Types**: commission, payment_processing, subscription, listing_fee, transaction_fee
- [ ] **Fee Structures**: percentage, fixed_amount, tiered
- [ ] **Validation**: Percentage 0-100%
- [ ] **Tiered Fees**: Multiple tiers work correctly

### Break-Even Calculator
- [ ] **Formula**: `breakEven = fixedCosts / (1 - variableRate)`
- [ ] **Edge Case**: Division by zero prevented
- [ ] **Edge Case**: Variable rate >= 100% prevented
- [ ] **All Cost Types**: Included in calculation
- [ ] **Channel Costs**: Applied correctly

### Margin Protection
- [ ] **Event Types**: margin_below_threshold, cost_increase, competitive_pricing
- [ ] **Status**: blocked, approved, rejected
- [ ] **Workflow**: blocked → approved/rejected
- [ ] **Expiration**: Auto-reject expired events
- [ ] **Permissions**: Only admins can approve

### Pricing Strategy
- [ ] **Strategy Types**: cost_plus, competitive, value_based, dynamic
- [ ] **Psychological Pricing**: .99, .95, .00, none
- [ ] **Product-Channel**: Strategies per product-channel combo
- [ ] **Auto-Calculate**: Prices update on cost changes

### Permissions
- [ ] **Admin**: Full access to all features
- [ ] **Pricing Manager**: Create/edit costs and strategies
- [ ] **Channel Manager**: Create/edit channel costs
- [ ] **Read Only**: View-only access
- [ ] **ACL**: 27 permissions enforced

### Multi-Tenancy
- [ ] **Tenant Isolation**: All models scoped by tenant_id
- [ ] **Cross-Tenant**: Access prevented
- [ ] **Auto-Assignment**: tenant_id set from context

### API
- [ ] **Pagination**: Default 20, max 100 per page
- [ ] **Filtering**: All filters work
- [ ] **Response Format**: Consistent JSON structure
- [ ] **Error Handling**: Proper status codes and messages
