# Product Requirements Document (PRD)

## ECOM-OS: Unified E-Commerce Operations Platform

### Next-Generation Features for UnoPim

**Document Version**: 1.0  
**Date**: February 14, 2026  
**Status**: Draft for Review  
**Author**: Product Team  
**Based on**: Market Research Report & ECOM-OS Specification

---

# Executive Summary

This PRD defines the next phase of UnoPim's evolution from a Product Information Management (PIM) system to a **comprehensive e-commerce operations platform**. By integrating pricing intelligence, budget management, inventory optimization, and unified analytics, UnoPim will become the **single source of truth** for all e-commerce operations.

## Strategic Vision

**"List once. Price smart. Control budgets. Sell everywhere."**

Transform UnoPim from a PIM into an **E-commerce Operating System (ECOM-OS)** that enables businesses to manage products, pricing, inventory, budgets, and analytics in one unified platform.

## Business Case

### Market Opportunity

- **TAM**: $37B PIM market by 2030 (16.09% CAGR)
- **SAM**: $12B mid-market e-commerce operations
- **SOM**: $2B multi-channel retailers seeking unified operations

### Competitive Differentiation

This positions UnoPim uniquely against:

- **PIM competitors**: No pricing/budget features
- **ERP systems**: Too complex and expensive
- **Point solutions**: Fragmented and disconnected
- **Marketplace tools**: Limited to specific channels
/Users/abdulrahmangamal/VSCodeProjects /Product_Information_Management/unopim/ECOM-OS_PRD_Next_Features.md

### Expected Impact

- **3x increase** in enterprise deal size ($60K → $180K ACV)
- **50% faster** time-to-value for customers
- **80% reduction** in operational tool stack
- **40% improvement** in customer retention

---

# Product Overview

## Core Concept

A centralized e-commerce operations platform that enables businesses to:

1. **List products once**, manage operations, pricing, budgets, and analytics in one view
2. **Make smarter, profit-driven decisions** with unified data
3. **Sync across all channels** with zero data conflicts
4. **Optimize profitability** with intelligent pricing and inventory allocation

## The Single Source of Truth

The platform becomes the central hub for:

| Data Domain | Current State | Future State (ECOM-OS) |
|-------------|---------------|------------------------|
| Products | ✅ PIM Core | ✅ Enhanced with profitability data |
| Inventory | ⚠️ Basic quantities | ✅ Profit-driven allocation |
| Orders | ❌ Channel-specific | ✅ Unified order management |
| Pricing | ❌ Manual/spreadsheets | ✅ Intelligent pricing engine |
| Budgets | ❌ External tools | ✅ Integrated budget tracking |
| Analytics | ⚠️ Basic dashboards | ✅ Unified analytics hub |
| Shipping | ❌ Channel-specific | ✅ Unified shipping rules |
| Performance | ❌ No insights | ✅ Real-time intelligence |

---

# Feature Requirements

## 1. Unified Multi-Channel Listing (OMNI-LIST)

### 1.1 Core Functionality

**Epic**: List Once, Sell Everywhere

**User Story**: As an e-commerce manager, I want to create products once and automatically sync them to all my sales channels so that I can eliminate duplicate work and data inconsistencies.

#### 1.1.1 Universal Product Catalog

**Priority**: P0 (MVP)

**Requirements**:

- [ ] Single product creation interface
- [ ] Channel-specific attribute mapping
- [ ] Automatic field validation per channel
- [ ] Bulk product creation from CSV/Excel
- [ ] AI-powered content generation for all channels

**Acceptance Criteria**:

```gherkin
GIVEN I have created a product in UnoPim
WHEN I configure channel mappings
THEN the product is automatically formatted for each channel
AND all required fields are validated before sync
AND I can preview channel-specific listings
```

#### 1.1.2 Supported Channels (Phase 1)

**Priority**: P0 (MVP)

| Channel | Type | Status | Priority |
|---------|------|--------|----------|
| Shopify | Store | ✅ Existing | P0 |
| Salla | Store (MENA) | 🔜 New | P0 |
| Easy Orders | Marketplace (MENA) | 🔜 New | P0 |
| Amazon | Marketplace | 🔜 New | P1 |
| eBay | Marketplace | 🔜 New | P1 |
| Noon | Marketplace (MENA) | 🔜 New | P2 |
| WooCommerce | Store | 🔜 New | P2 |
| Magento | Store | 🔜 New | P3 |

**Technical Requirements**:

```
Channel Integration Architecture:
├── Unified Channel Interface (abstract layer)
│   ├── Product Sync Contract
│   ├── Inventory Sync Contract
│   ├── Order Sync Contract
│   └── Price Sync Contract
├── Channel Adapters
│   ├── Shopify Adapter (existing, enhance)
│   ├── Salla Adapter (new)
│   ├── Easy Orders Adapter (new)
│   └── [Future channels...]
└── Sync Engine
    ├── Real-time sync (webhooks)
    ├── Scheduled sync (queues)
    └── Conflict resolution
```

#### 1.1.3 Channel-Specific Rules Engine

**Priority**: P1

**Requirements**:

- [ ] Visual rule builder for channel mappings
- [ ] Conditional logic (if/then rules)
- [ ] Attribute transformations
- [ ] Default value assignment
- [ ] Validation rules per channel

**Example Rules**:

```yaml
Rule: Amazon Title Optimization
  IF: channel = "amazon"
  THEN:
    - title = CONCAT(product.name, " - ", product.brand, " (", product.color, ")")
    - max_length = 200
    - prohibited_words = ["best", "free", "guarantee"]
    
Rule: Shopify Price Markup
  IF: channel = "shopify" AND product.category = "electronics"
  THEN:
    - price = base_price * 1.15
    - compare_at_price = base_price * 1.30
```

#### 1.1.4 Sync Conflict Resolution

**Priority**: P1

**Requirements**:

- [ ] Automatic conflict detection
- [ ] Conflict resolution strategies:
  - Source wins (UnoPim is master)
  - Latest wins (most recent update)
  - Manual resolution queue
- [ ] Conflict audit log
- [ ] Notification system

**Data Model**:

```sql
CREATE TABLE channel_sync_conflicts (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    channel_code VARCHAR(50),
    conflict_type ENUM('price', 'inventory', 'attributes'),
    local_value JSON,
    remote_value JSON,
    resolution_status ENUM('pending', 'resolved', 'ignored'),
    resolved_by BIGINT,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP
);
```

### 1.2 Technical Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     UnoPim Core (PIM)                        │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │  Products   │  │  Attributes  │  │   Categories     │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              ECOM-OS Operations Layer (NEW)                  │
│  ┌──────────────┐  ┌─────────────┐  ┌──────────────────┐   │
│  │ Channel Mgmt │  │ Pricing Eng │  │ Budget Tracker   │   │
│  └──────────────┘  └─────────────┘  └──────────────────┘   │
│  ┌──────────────┐  ┌─────────────┐  ┌──────────────────┐   │
│  │ Inventory    │  │ Analytics   │  │ Order Aggregator │   │
│  └──────────────┘  └─────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Channel Integration Layer                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ Shopify  │  │  Salla   │  │EasyOrders│  │ [Future] │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Centralized Pricing Engine (PRICE-ENG)

### 2.1 Profit-Driven Pricing

**Epic**: Intelligent Pricing & Profitability

**User Story**: As a business owner, I want to automatically calculate optimal selling prices based on all my costs so that I can protect my profit margins across all channels.

#### 2.1.1 Cost Structure Management

**Priority**: P0 (MVP)

**Requirements**:

**A. Cost Components**

```yaml
Product Cost Structure:
  COGS (Cost of Goods Sold):
    - Base cost from supplier
    - Import duties & taxes
    - Quality inspection costs
  
  Operational Costs:
    - Warehousing per unit
    - Handling & picking
    - Packaging materials
    
  Shipping Costs:
    - Base shipping rate per zone
    - Weight-based pricing
    - Express vs. standard
    
  Marketing Costs:
    - Cost Per Purchase (CPP)
    - Ad spend allocation per SKU
    - Influencer/affiliate commissions
    
  Platform Costs:
    - Channel commission (%)
    - Payment processing fees
    - Subscription costs
    
  Overhead:
    - Employee costs allocation
    - Rent/utilities allocation
    - Insurance & misc
```

**B. Cost Data Model**

```sql
CREATE TABLE product_costs (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    tenant_id BIGINT,
    
    -- COGS
    base_cost DECIMAL(12,2),
    import_duties DECIMAL(12,2),
    quality_costs DECIMAL(12,2),
    
    -- Operational
    warehousing_cost DECIMAL(12,2),
    handling_cost DECIMAL(12,2),
    packaging_cost DECIMAL(12,2),
    
    -- Marketing (averages)
    avg_cpp DECIMAL(12,2),
    avg_ad_spend DECIMAL(12,2),
    
    -- Metadata
    currency VARCHAR(3),
    effective_from DATE,
    effective_to DATE,
    updated_by BIGINT,
    updated_at TIMESTAMP,
    
    INDEX idx_product_tenant (product_id, tenant_id),
    INDEX idx_effective_dates (effective_from, effective_to)
);

CREATE TABLE channel_costs (
    id BIGINT PRIMARY KEY,
    channel_code VARCHAR(50),
    product_id BIGINT,
    tenant_id BIGINT,
    
    commission_percentage DECIMAL(5,2),
    fixed_fee DECIMAL(12,2),
    payment_fee_percentage DECIMAL(5,2),
    
    -- Shipping by zone
    shipping_cost_zone_a DECIMAL(12,2),
    shipping_cost_zone_b DECIMAL(12,2),
    shipping_cost_zone_c DECIMAL(12,2),
    
    effective_from DATE,
    effective_to DATE,
    
    INDEX idx_channel_product (channel_code, product_id)
);
```

**C. UI Requirements**

```
Cost Management Screen:
┌────────────────────────────────────────────────────────────┐
│ Product: Wireless Earbuds Pro - Cost Breakdown            │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ COGS                                                       │
│ ├─ Base Cost (Supplier)      $ 45.00  [Edit]              │
│ ├─ Import Duties (5%)        $  2.25  [Edit]              │
│ └─ Quality Inspection        $  0.50  [Edit]              │
│ SUBTOTAL COGS                $ 47.75                      │
│                                                            │
│ Operational Costs                                          │
│ ├─ Warehousing               $  1.20  [Edit]              │
│ ├─ Handling & Picking        $  0.80  [Edit]              │
│ └─ Packaging                 $  1.50  [Edit]              │
│ SUBTOTAL OPERATIONAL         $  3.50                      │
│                                                            │
│ Channel-Specific Costs (Shopify)                          │
│ ├─ Commission (2.9% + $0.30) Calculated at price         │
│ ├─ Payment Processing        2.9% of price                │
│ └─ Avg Ad Spend (CPP)        $  4.50  [Edit]              │
│                                                            │
│ ┌──────────────────────────────────────────────────────┐ │
│ │ TOTAL COST (at $79.99 selling price)                 │ │
│ │   $47.75 + $3.50 + $2.32 + $4.50 = $58.07           │ │
│ │                                                      │ │
│ │ PROFIT: $21.92 (27.4% margin)  ✓ HEALTHY            │ │
│ └──────────────────────────────────────────────────────┘ │
│                                                            │
│ [Save] [Calculate Break-even] [View History]              │
└────────────────────────────────────────────────────────────┘
```

#### 2.1.2 Break-Even Price Calculator

**Priority**: P0 (MVP)

**Requirements**:

- [ ] Real-time break-even calculation
- [ ] Channel-specific break-even (different fees per channel)
- [ ] Volume-based cost adjustments
- [ ] Break-even timeline projections

**Algorithm**:

```php
class BreakEvenCalculator {
    public function calculate(
        Product $product,
        Channel $channel,
        float $targetMargin = 0.0
    ): BreakEvenResult {
        // Get all costs
        $cogs = $product->getTotalCOGS();
        $operational = $product->getOperationalCosts();
        $marketing = $product->getAvgMarketingCost();
        
        // Get channel costs
        $commissionRate = $channel->commission_percentage / 100;
        $paymentFeeRate = $channel->payment_fee_percentage / 100;
        $fixedFees = $channel->fixed_fee;
        
        // Break-even formula (accounting for %-based fees)
        // Let x = selling price
        // x = costs + (commission_rate * x) + (payment_rate * x)
        // x - (commission_rate * x) - (payment_rate * x) = costs
        // x * (1 - commission_rate - payment_rate) = costs
        // x = costs / (1 - commission_rate - payment_rate)
        
        $fixedCosts = $cogs + $operational + $marketing + $fixedFees;
        $variableCostRate = 1 - $commissionRate - $paymentFeeRate;
        
        $breakEvenPrice = $fixedCosts / $variableCostRate;
        
        // With target margin
        $priceWithMargin = $breakEvenPrice / (1 - $targetMargin);
        
        return new BreakEvenResult(
            breakEvenPrice: $breakEvenPrice,
            priceWithMargin: $priceWithMargin,
            totalCosts: $fixedCosts,
            margin: $targetMargin,
            channel: $channel->code
        );
    }
}
```

#### 2.1.3 Recommended Selling Price Engine

**Priority**: P0 (MVP)

**Requirements**:

- [ ] Target margin configuration per category
- [ ] Competitive pricing analysis (optional integration)
- [ ] Price elasticity recommendations
- [ ] Channel-specific price optimization
- [ ] Psychological pricing rules ($X.99, $X.97, etc.)

**Configuration Model**:

```yaml
Pricing Strategy Configuration:
  
  Global Defaults:
    minimum_margin: 15%
    target_margin: 25%
    premium_margin: 40%
    
  Category Overrides:
    electronics:
      minimum_margin: 12%
      target_margin: 20%
      competitive_pricing: true
      
    fashion:
      minimum_margin: 30%
      target_margin: 50%
      seasonal_adjustments: true
      
  Channel Overrides:
    amazon:
      price_parity: true  # Match or beat other channels
      buy_box_optimization: true
      
    shopify:
      psychological_pricing: ".99"
      bundle_discounts: enabled
      
  Psychological Pricing Rules:
    - round_to: 0.99      # $19.99 instead of $20.00
    - avoid_digits: [4]   # Cultural considerations
    - charm_pricing: true # Prices ending in 7, 9
```

**UI Mockup**:

```
┌────────────────────────────────────────────────────────────┐
│ Pricing Recommendation - Wireless Earbuds Pro             │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ Cost Summary:                                              │
│   Total Costs: $58.07                                     │
│   Break-even Price: $60.42                                │
│                                                            │
│ ┌────────────────────────────────────────────────────────┐│
│ │ RECOMMENDED PRICES BY CHANNEL                          ││
│ ├────────────────────────────────────────────────────────┤│
│ │                                                        ││
│ │ Shopify Store                                          ││
│ │ ├─ Break-even:    $60.42                              ││
│ │ ├─ 15% Margin:    $71.08  ← MINIMUM                   ││
│ │ ├─ 25% Margin:    $80.56  ← RECOMMENDED ✓             ││
│ │ └─ 40% Margin:   $100.70  ← PREMIUM                   ││
│ │                                                        ││
│ │ Amazon (includes 15% referral fee)                    ││
│ │ ├─ Break-even:    $68.14                              ││
│ │ ├─ 15% Margin:    $80.16  ← MINIMUM                   ││
│ │ ├─ 25% Margin:    $90.85  ← RECOMMENDED ✓             ││
│ │ └─ 40% Margin:   $113.57  ← PREMIUM                   ││
│ │                                                        ││
│ │ Salla (MENA - includes VAT considerations)            ││
│ │ ├─ Break-even:    $62.18 (243.52 SAR)                 ││
│ │ ├─ 15% Margin:    $73.15 (286.38 SAR) ← MINIMUM       ││
│ │ ├─ 25% Margin:    $82.90 (324.56 SAR) ← RECOMMENDED   ││
│ │ └─ 40% Margin:   $103.63 (405.70 SAR) ← PREMIUM       ││
│ │                                                        ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ [Apply Recommendations] [Custom Pricing] [Export]         │
└────────────────────────────────────────────────────────────┘
```

#### 2.1.4 Margin Protection Rules

**Priority**: P0 (MVP)

**Requirements**:

- [ ] Minimum margin enforcement
- [ ] Price change approval workflows
- [ ] Low margin alerts and notifications
- [ ] Automatic price adjustment (optional)
- [ ] Margin exception reporting

**Business Rules**:

```yaml
Margin Protection Rules:

Rule 1: Minimum Margin Enforcement
  IF: calculated_margin < minimum_margin
  THEN:
    - BLOCK price save
    - SHOW warning: "Price below minimum margin of X%"
    - REQUIRE manager approval
    
Rule 2: Low Margin Warning
  IF: calculated_margin < target_margin
  AND: calculated_margin >= minimum_margin
  THEN:
    - SHOW warning: "Price below target margin"
    - ALLOW save with acknowledgment
    
Rule 3: Competitor Price Match (Optional)
  IF: competitor_price < our_break_even
  THEN:
    - ALERT: "Cannot match competitor price profitably"
    - SUGGEST: alternative strategies (bundle, reduce costs)
    
Rule 4: Channel Price Parity
  IF: price_difference_between_channels > 10%
  THEN:
    - WARNING: "Significant price variance across channels"
    - REQUIRE: explicit confirmation
```

**Data Model**:

```sql
CREATE TABLE margin_protection_events (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    channel_code VARCHAR(50),
    tenant_id BIGINT,
    
    attempted_price DECIMAL(12,2),
    calculated_margin DECIMAL(5,2),
    minimum_margin DECIMAL(5,2),
    
    event_type ENUM('blocked', 'warning', 'approved'),
    triggered_by BIGINT,
    approved_by BIGINT,
    approval_reason TEXT,
    
    created_at TIMESTAMP,
    
    INDEX idx_product_margin (product_id, calculated_margin)
);
```

### 2.2 Multi-Currency & Regional Pricing

**Priority**: P1

**Requirements**:

- [ ] Currency-specific cost tracking
- [ ] Exchange rate integration
- [ ] Regional pricing strategies
- [ ] VAT/tax handling by region
- [ ] Price localization

---

## 3. Budget Management & Tracking (BUDGET-TRK)

### 3.1 Marketing Budget Management

**Epic**: Budget Intelligence

**User Story**: As a marketing manager, I want to track my ad spend and marketing budgets per SKU and channel so that I can optimize my marketing ROI and make data-driven budget allocation decisions.

#### 3.1.1 Budget Structure

**Priority**: P1

**Data Model**:

```sql
CREATE TABLE marketing_budgets (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT,
    
    -- Budget scope
    budget_type ENUM('global', 'channel', 'category', 'sku'),
    scope_id BIGINT, -- channel_id, category_id, or product_id
    
    -- Budget details
    name VARCHAR(255),
    total_budget DECIMAL(15,2),
    currency VARCHAR(3),
    
    -- Time period
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly'),
    start_date DATE,
    end_date DATE,
    
    -- Status
    status ENUM('active', 'paused', 'exhausted', 'completed'),
    
    -- Metadata
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_tenant_period (tenant_id, start_date, end_date)
);

CREATE TABLE budget_spend_log (
    id BIGINT PRIMARY KEY,
    budget_id BIGINT,
    tenant_id BIGINT,
    
    -- Spend details
    amount DECIMAL(15,2),
    currency VARCHAR(3),
    spend_date DATE,
    
    -- Attribution
    channel_code VARCHAR(50),
    product_id BIGINT,
    campaign_name VARCHAR(255),
    ad_set_name VARCHAR(255),
    
    -- Performance
    impressions BIGINT,
    clicks BIGINT,
    conversions BIGINT,
    revenue_attributed DECIMAL(15,2),
    
    -- Source
    data_source VARCHAR(50), -- 'manual', 'meta_api', 'google_api'
    source_reference VARCHAR(255),
    
    created_at TIMESTAMP,
    
    INDEX idx_budget_date (budget_id, spend_date),
    INDEX idx_product_channel (product_id, channel_code)
);
```

#### 3.1.2 Ad Spend Tracking

**Priority**: P1

**Requirements**:

- [ ] Manual budget entry
- [ ] API integrations for auto-import:
  - [ ] Meta Ads (Facebook/Instagram)
  - [ ] Google Ads
  - [ ] TikTok Ads
  - [ ] Snapchat Ads
- [ ] Cost Per Purchase (CPP) calculation
- [ ] Budget utilization tracking
- [ ] Overspend alerts

**UI Mockup**:

```
┌────────────────────────────────────────────────────────────┐
│ Marketing Budget Dashboard - February 2026                │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ Overall Budget Status                                      │
│ ┌────────────────────────────────────────────────────────┐│
│ │ Total Budget: $50,000                                  ││
│ │ Spent: $32,450 (64.9%)                                ││
│ │ Remaining: $17,550                                     ││
│ │                                                        ││
│ │ ██████████████████████████░░░░░░░░░░░ 64.9%           ││
│ │                                                        ││
│ │ Days Remaining: 14 | Daily Avg: $2,317                ││
│ │ Projected Spend: $47,850 (Under budget ✓)             ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ Budget by Channel                                          │
│ ┌────────────────────────────────────────────────────────┐│
│ │ Channel      │ Budget  │ Spent   │ ROI   │ CPP   │Status│
│ ├────────────────────────────────────────────────────────┤│
│ │ Meta Ads     │ $25,000 │ $18,200 │ 285%  │ $4.20 │  ✓  ││
│ │ Google Ads   │ $15,000 │ $ 9,850 │ 210%  │ $6.80 │  ✓  ││
│ │ TikTok Ads   │ $10,000 │ $ 4,400 │ 150%  │ $8.50 │  ⚠  ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ Top Performing SKUs                                        │
│ ┌────────────────────────────────────────────────────────┐│
│ │ SKU           │ Ad Spend │ Revenue │ ROAS  │ Margin   ││
│ ├────────────────────────────────────────────────────────┤│
│ │ WBE-PRO-001   │ $2,340   │ $12,500 │ 5.34x │ 32%     ││
│ │ WBE-LITE-002  │ $1,850   │ $ 8,200 │ 4.43x │ 28%     ││
│ │ CSE-COMF-003  │ $3,200   │ $11,800 │ 3.69x │ 24%     ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ [Add Spend] [Connect Ad Account] [Export Report]          │
└────────────────────────────────────────────────────────────┘
```

#### 3.1.3 Cost Per Purchase (CPP) Calculator

**Priority**: P1

**Requirements**:

- [ ] Real-time CPP calculation per SKU
- [ ] CPP trend analysis
- [ ] CPP vs. margin comparison
- [ ] Channel-specific CPP tracking

**Formula**:

```
CPP = Total Ad Spend / Total Purchases

Effective CPP = CPP + (Fixed Costs / Volume)

Break-even CPP = (Selling Price - All Other Costs) / 1
```

#### 3.1.4 Budget vs. Performance Analytics

**Priority**: P1

**Requirements**:

- [ ] ROAS (Return on Ad Spend) tracking
- [ ] CAC (Customer Acquisition Cost)
- [ ] LTV:CAC ratio
- [ ] Budget efficiency scoring
- [ ] Optimization recommendations

---

## 4. Smart Inventory Management (SMART-INV)

### 4.1 Profit-Driven Stock Allocation

**Epic**: Intelligent Inventory Optimization

**User Story**: As an operations manager, I want to allocate inventory based on profitability and demand so that I maximize revenue while minimizing cash tied up in slow-moving stock.

#### 4.1.1 Inventory Intelligence Engine

**Priority**: P1

**Requirements**:

- [ ] SKU profitability scoring
- [ ] Run rate calculation per channel
- [ ] Stock velocity analysis
- [ ] ABC classification (A/B/C based on value)
- [ ] Dead stock identification

**Data Model**:

```sql
CREATE TABLE inventory_intelligence (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    channel_code VARCHAR(50),
    tenant_id BIGINT,
    
    -- Profitability metrics
    gross_margin DECIMAL(5,2),
    contribution_margin DECIMAL(5,2),
    profitability_score DECIMAL(5,2), -- Composite score
    
    -- Velocity metrics
    units_sold_7d BIGINT,
    units_sold_30d BIGINT,
    units_sold_90d BIGINT,
    run_rate_daily DECIMAL(10,2),
    run_rate_monthly DECIMAL(10,2),
    
    -- Stock metrics
    current_stock BIGINT,
    days_of_stock DECIMAL(10,2),
    stock_velocity_score DECIMAL(5,2),
    
    -- Allocation
    recommended_allocation BIGINT,
    priority_score DECIMAL(5,2),
    abc_classification ENUM('A', 'B', 'C'),
    
    -- Budget efficiency
    cpp DECIMAL(12,2),
    roas DECIMAL(10,2),
    budget_efficiency_score DECIMAL(5,2),
    
    -- Timestamps
    calculated_at TIMESTAMP,
    
    INDEX idx_product_profit (product_id, profitability_score DESC),
    INDEX idx_channel_priority (channel_code, priority_score DESC)
);
```

#### 4.1.2 Stock Allocation Algorithm

**Priority**: P1

**Algorithm**:

```php
class SmartStockAllocator {
    public function allocate(
        int $totalStock,
        Collection $products,
        array $channels
    ): AllocationResult {
        
        // Step 1: Calculate composite scores
        foreach ($products as $product) {
            foreach ($channels as $channel) {
                $score = $this->calculatePriorityScore(
                    product: $product,
                    channel: $channel
                );
                $scores->push([
                    'product' => $product,
                    'channel' => $channel,
                    'score' => $score
                ]);
            }
        }
        
        // Sort by score (highest first)
        $scores = $scores->sortByDesc('score');
        
        // Step 2: Allocate stock proportionally
        $totalScore = $scores->sum('score');
        $allocation = collect();
        
        foreach ($scores as $item) {
            $share = $item['score'] / $totalScore;
            $allocated = (int) ($totalStock * $share);
            
            $allocation->push([
                'product_id' => $item['product']->id,
                'channel' => $item['channel']->code,
                'allocated_stock' => $allocated,
                'priority_score' => $item['score'],
                'expected_revenue' => $allocated * $item['product']->avg_selling_price,
                'expected_profit' => $allocated * $item['product']->unit_profit
            ]);
        }
        
        return new AllocationResult(
            allocations: $allocation,
            totalStock: $totalStock,
            expectedRevenue: $allocation->sum('expected_revenue'),
            expectedProfit: $allocation->sum('expected_profit')
        );
    }
    
    private function calculatePriorityScore(
        Product $product,
        Channel $channel
    ): float {
        // Weighted composite score
        $profitability = $product->profitability_score * 0.35;
        $velocity = $product->stock_velocity_score * 0.25;
        $budgetEfficiency = $product->budget_efficiency_score * 0.20;
        $channelPerformance = $channel->performance_score * 0.20;
        
        return $profitability + $velocity + $budgetEfficiency + $channelPerformance;
    }
}
```

#### 4.1.3 Stock Availability Protection

**Priority**: P1

**Requirements**:

- [ ] Reserve stock for high-performing channels
- [ ] Buffer stock for unexpected demand
- [ ] Safety stock calculations
- [ ] Stock-out prevention alerts

**Configuration**:

```yaml
Stock Protection Rules:

High-Performers Protection:
  IF: product.abc_classification = 'A'
  THEN:
    - reserve_percentage: 30%
    - minimum_buffer: 50 units
    - alert_threshold: 14 days of stock
    
Standard Products:
  IF: product.abc_classification = 'B'
  THEN:
    - reserve_percentage: 15%
    - minimum_buffer: 20 units
    - alert_threshold: 21 days of stock
    
Low-Performers:
  IF: product.abc_classification = 'C'
  THEN:
    - reserve_percentage: 0%
    - minimum_buffer: 0 units
    - alert_threshold: 30 days of stock
    - auto_discount: true  # Suggest clearance pricing
```

#### 4.1.4 Dead Stock Identification

**Priority**: P2

**Requirements**:

- [ ] Identify slow-moving inventory
- [ ] Calculate carrying costs
- [ ] Generate clearance recommendations
- [ ] Track dead stock reduction

**Criteria**:

```php
class DeadStockIdentifier {
    public function identify(Collection $products): Collection {
        return $products->filter(function ($product) {
            // No sales in 90 days
            $noRecentSales = $product->units_sold_90d == 0;
            
            // Low velocity (less than 1 unit per month)
            $lowVelocity = $product->run_rate_monthly < 1;
            
            // High days of stock (> 180 days)
            $excessStock = $product->days_of_stock > 180;
            
            // High carrying cost
            $highCarryingCost = $product->carrying_cost > ($product->cost * 0.25);
            
            return $noRecentSales || ($lowVelocity && $excessStock);
        })->map(function ($product) {
            return [
                'product' => $product,
                'dead_stock_units' => $product->current_stock,
                'dead_stock_value' => $product->current_stock * $product->cost,
                'carrying_cost_monthly' => $product->current_stock * $product->unit_carrying_cost,
                'recommended_action' => $this->getRecommendation($product),
                'suggested_discount' => $this->calculateClearanceDiscount($product)
            ];
        });
    }
}
```

---

## 5. Unified Analytics Dashboard (ANALYTICS-UNI)

### 5.1 Executive Dashboard

**Epic**: Data-Driven Decision Making

**User Story**: As a business owner, I want to see all my key metrics in one place so that I can quickly understand business health and make informed decisions.

#### 5.1.1 KPI Overview

**Priority**: P0 (MVP)

**Key Metrics**:

```yaml
Dashboard KPIs:

Revenue Metrics:
  - Total Revenue (all channels)
  - Revenue by Channel
  - Revenue Growth Rate
  - Average Order Value (AOV)
  
Profitability Metrics:
  - Gross Profit
  - Net Profit
  - Profit Margin %
  - Profit per SKU
  
Operational Metrics:
  - Total Orders
  - Order Fulfillment Rate
  - Average Delivery Time
  - Return Rate
  
Inventory Metrics:
  - Total SKUs
  - Stock Value
  - Days of Inventory
  - Stock-out Rate
  
Marketing Metrics:
  - Total Ad Spend
  - ROAS (Return on Ad Spend)
  - CPP (Cost Per Purchase)
  - CAC (Customer Acquisition Cost)
  
Channel Performance:
  - Revenue by Channel
  - Margin by Channel
  - Volume by Channel
  - Efficiency Score by Channel
```

**UI Mockup**:

```
┌────────────────────────────────────────────────────────────────┐
│ Executive Dashboard - February 14, 2026                        │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ ┌─────────────────────┐  ┌─────────────────────┐             │
│ │ Total Revenue       │  │ Gross Profit        │             │
│ │ $247,850            │  │ $ 89,250            │             │
│ │ ↑ 12.3% vs last month│  │ 36.0% margin       │             │
│ └─────────────────────┘  └─────────────────────┘             │
│                                                                │
│ ┌─────────────────────┐  ┌─────────────────────┐             │
│ │ Total Orders        │  │ Avg Order Value     │             │
│ │ 3,245               │  │ $76.38              │             │
│ │ ↑ 8.5% vs last month│  │ ↑ 3.2% vs last month│             │
│ └─────────────────────┘  └─────────────────────┘             │
│                                                                │
│ Revenue by Channel          │  Profit Margin by Channel        │
│ ┌──────────────────────────┐│ ┌──────────────────────────┐   │
│ │ ████████████ Shopify 42% ││ │ ██████████ Shopify 38%   │   │
│ │ ███████ Salla 28%        ││ │ ███████ Salla 34%        │   │
│ │ ████ EasyOrders 18%      ││ │ ████ Amazon 28%          │   │
│ │ ███ Amazon 12%           ││ │ ██ EasyOrders 22%        │   │
│ └──────────────────────────┘│ └──────────────────────────┘   │
│                                                                │
│ Performance Alerts                                             │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ ⚠️ 5 SKUs below minimum margin - Action Required         │ │
│ │ ✓ Shopify channel exceeding targets                       │ │
│ │ ⚠️ TikTok ads CPP increased 15% - Review budget          │ │
│ │ ⚠️ 12 SKUs low stock (<14 days) - Reorder needed         │ │
│ └──────────────────────────────────────────────────────────┘ │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

#### 5.1.2 SKU-Level Analytics

**Priority**: P1

**Requirements**:

- [ ] Individual SKU performance cards
- [ ] SKU profitability ranking
- [ ] SKU lifecycle analysis
- [ ] Cross-sell/upsell identification

#### 5.1.3 Channel-Level Analytics

**Priority**: P1

**Requirements**:

- [ ] Per-channel performance comparison
- [ ] Channel efficiency scoring
- [ ] Channel-specific recommendations
- [ ] Channel growth opportunities

#### 5.1.4 Real-Time Performance Monitoring

**Priority**: P2

**Requirements**:

- [ ] Live sales ticker
- [ ] Real-time inventory updates
- [ ] Live order status
- [ ] Instant alerts

---

## 6. Unified Order Management (ORDER-UNI)

### 6.1 Order Aggregation

**Epic**: Single View of All Orders

**User Story**: As a customer service agent, I want to see all orders from all channels in one place so that I can quickly help customers regardless of where they purchased.

#### 6.1.1 Unified Order Feed

**Priority**: P1

**Data Model**:

```sql
CREATE TABLE unified_orders (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT,
    
    -- Channel reference
    channel_code VARCHAR(50),
    channel_order_id VARCHAR(255),
    
    -- Customer info
    customer_email VARCHAR(255),
    customer_name VARCHAR(255),
    customer_phone VARCHAR(50),
    
    -- Order details
    order_number VARCHAR(100),
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded'),
    
    -- Financials
    subtotal DECIMAL(15,2),
    discount DECIMAL(15,2),
    tax DECIMAL(15,2),
    shipping DECIMAL(15,2),
    total DECIMAL(15,2),
    currency VARCHAR(3),
    
    -- Profitability
    cogs DECIMAL(15,2),
    gross_profit DECIMAL(15,2),
    channel_fees DECIMAL(15,2),
    net_profit DECIMAL(15,2),
    
    -- Fulfillment
    fulfillment_status ENUM('unfulfilled', 'partial', 'fulfilled'),
    shipping_carrier VARCHAR(100),
    tracking_number VARCHAR(255),
    shipped_at TIMESTAMP,
    delivered_at TIMESTAMP,
    
    -- Timestamps
    ordered_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_tenant_status (tenant_id, order_status),
    INDEX idx_tenant_date (tenant_id, ordered_at),
    INDEX idx_channel_order (channel_code, channel_order_id)
);

CREATE TABLE unified_order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT,
    tenant_id BIGINT,
    
    product_id BIGINT,
    sku VARCHAR(100),
    product_name VARCHAR(500),
    
    quantity BIGINT,
    unit_price DECIMAL(15,2),
    unit_cost DECIMAL(15,2),
    unit_profit DECIMAL(15,2),
    total DECIMAL(15,2),
    
    -- Attribution
    attributed_ad_spend DECIMAL(15,2),
    
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);
```

#### 6.1.2 Order Sync & Reconciliation

**Priority**: P1

**Requirements**:

- [ ] Real-time order sync from all channels
- [ ] Order status synchronization
- [ ] Inventory deduction on order
- [ ] Order acknowledgment to channels

#### 6.1.3 Fulfillment Management

**Priority**: P2

**Requirements**:

- [ ] Unified fulfillment workflow
- [ ] Shipping carrier integration
- [ ] Tracking number sync
- [ ] Delivery confirmation

---

## 7. Shipping Management (SHIP-MGT)

### 7.1 Unified Shipping Rules

**Priority**: P2

**Requirements**:

- [ ] Shipping zone configuration
- [ ] Carrier rate management
- [ ] Shipping cost allocation
- [ ] Delivery performance tracking

---

## 8. Integration Layer (INTEGRATION-API)

### 8.1 External Integrations

**Priority**: P1

**Required Integrations**:

```yaml
Phase 1 (MVP):
  Channels:
    - Shopify (✅ existing, enhance)
    - Salla (new - MENA focus)
    - Easy Orders (new - MENA focus)
    
  Marketing:
    - Meta Ads API
    - Google Ads API
    
Phase 2:
  Channels:
    - Amazon
    - eBay
    - Noon
    
  Marketing:
    - TikTok Ads
    - Snapchat Ads
    
  Shipping:
    - Aramex
    - SMSA
    - DHL
    
Phase 3:
  Channels:
    - WooCommerce
    - Magento
    - Etsy
    
  Analytics:
    - Google Analytics
    - Facebook Pixel
```

### 8.2 API Architecture

**Requirements**:

- [ ] Unified webhook system
- [ ] Rate limiting per integration
- [ ] Error handling and retry logic
- [ ] Audit logging
- [ ] Integration health monitoring

---

# Technical Architecture

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  Admin UI    │  │  API Gateway │  │  Webhooks    │              │
│  │  (Vue.js)    │  │  (REST/GraphQL)│  │  Endpoint   │              │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         APPLICATION LAYER                            │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    ECOM-OS Core Services                      │  │
│  │                                                                │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐              │  │
│  │  │  PIM Core  │  │  Pricing   │  │  Budget    │              │  │
│  │  │  Service   │  │  Engine    │  │  Tracker   │              │  │
│  │  └────────────┘  └────────────┘  └────────────┘              │  │
│  │                                                                │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐              │  │
│  │  │ Inventory  │  │ Analytics  │  │  Order     │              │  │
│  │  │ Intelligence│  │  Engine    │  │  Manager   │              │  │
│  │  └────────────┘  └────────────┘  └────────────┘              │  │
│  │                                                                │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐              │  │
│  │  │  Channel   │  │  Sync      │  │  Workflow  │              │  │
│  │  │  Manager   │  │  Engine    │  │  Engine    │              │  │
│  │  └────────────┘  └────────────┘  └────────────┘              │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    Integration Layer                          │  │
│  │                                                                │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐              │  │
│  │  │  Shopify   │  │   Salla    │  │EasyOrders  │              │  │
│  │  │  Adapter   │  │  Adapter   │  │  Adapter   │              │  │
│  │  └────────────┘  └────────────┘  └────────────┘              │  │
│  │                                                                │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐              │  │
│  │  │  Meta Ads  │  │ Google Ads │  │  [More...] │              │  │
│  │  │  Adapter   │  │  Adapter   │  │            │              │  │
│  │  └────────────┘  └────────────┘  └────────────┘              │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                          DATA LAYER                                  │
│                                                                      │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │ PostgreSQL │  │Elasticsearch│  │   Redis    │  │   S3       │   │
│  │ (Primary)  │  │  (Search)  │  │  (Cache)   │  │ (Files)    │   │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        INFRASTRUCTURE                                │
│                                                                      │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │   Queue    │  │  Scheduler │  │ Monitoring │  │   Logs     │   │
│  │  Workers   │  │   (Cron)   │  │(Laravel Telescope)│         │   │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## Database Schema Additions

```sql
-- New tables for ECOM-OS features

-- 1. Channel Management
CREATE TABLE channels (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT,
    code VARCHAR(50),
    name VARCHAR(255),
    type ENUM('store', 'marketplace', 'social'),
    
    -- Connection details
    api_endpoint VARCHAR(500),
    api_credentials JSON,
    webhook_url VARCHAR(500),
    
    -- Configuration
    commission_percentage DECIMAL(5,2),
    payment_fee_percentage DECIMAL(5,2),
    fixed_fee DECIMAL(12,2),
    
    status ENUM('active', 'inactive', 'error'),
    last_sync_at TIMESTAMP,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY uk_tenant_code (tenant_id, code)
);

-- 2. Sync Jobs
CREATE TABLE channel_sync_jobs (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT,
    channel_id BIGINT,
    
    job_type ENUM('product_sync', 'inventory_sync', 'order_sync', 'price_sync'),
    direction ENUM('import', 'export'),
    
    status ENUM('pending', 'processing', 'completed', 'failed'),
    
    total_records BIGINT,
    processed_records BIGINT,
    failed_records BIGINT,
    
    error_log TEXT,
    
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP
);

-- 3. Product Channel Mapping
CREATE TABLE product_channel_mappings (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    channel_id BIGINT,
    tenant_id BIGINT,
    
    -- Channel-specific IDs
    channel_product_id VARCHAR(255),
    channel_variant_id VARCHAR(255),
    
    -- Channel-specific attributes
    channel_title VARCHAR(500),
    channel_description TEXT,
    channel_price DECIMAL(12,2),
    
    -- Sync status
    sync_status ENUM('synced', 'pending', 'error'),
    last_sync_at TIMESTAMP,
    sync_errors JSON,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY uk_product_channel (product_id, channel_id)
);

-- Additional tables defined in respective feature sections above
```

---

# Implementation Roadmap

## Phase 1: Foundation (Q2 2026) - 12 Weeks

### Sprint 1-2: Core Infrastructure

**Priority**: P0

- [ ] Database schema migrations
- [ ] Channel management module
- [ ] Integration framework
- [ ] API structure

### Sprint 3-4: Channel Connectors

**Priority**: P0

- [ ] Shopify integration enhancement
- [ ] Salla adapter development
- [ ] Easy Orders adapter development
- [ ] Product sync workflows

### Sprint 5-6: Pricing Engine

**Priority**: P0

- [ ] Cost structure management
- [ ] Break-even calculator
- [ ] Recommended pricing
- [ ] Margin protection rules

### Sprint 7-8: Basic Analytics

**Priority**: P0

- [ ] Executive dashboard
- [ ] Channel performance views
- [ ] Basic reporting

### Sprint 9-10: Order Aggregation

**Priority**: P1

- [ ] Unified order model
- [ ] Order sync from channels
- [ ] Order management UI

### Sprint 11-12: MVP Polish

**Priority**: P0

- [ ] UI/UX refinement
- [ ] Performance optimization
- [ ] Documentation
- [ ] Beta testing preparation

**Deliverable**: MVP Release with core features

---

## Phase 2: Intelligence (Q3 2026) - 12 Weeks

### Sprint 13-15: Budget Management

**Priority**: P1

- [ ] Marketing budget tracking
- [ ] Ad spend import (Meta, Google)
- [ ] CPP calculations
- [ ] Budget analytics

### Sprint 16-18: Inventory Intelligence

**Priority**: P1

- [ ] SKU profitability scoring
- [ ] Run rate calculations
- [ ] Smart allocation algorithm
- [ ] Dead stock identification

### Sprint 19-21: Advanced Analytics

**Priority**: P1

- [ ] SKU-level analytics
- [ ] Profitability analysis
- [ ] Channel comparison
- [ ] Custom reports

### Sprint 22-24: Enhancements

**Priority**: P2

- [ ] Real-time dashboards
- [ ] Alert system
- [ ] Workflow automation
- [ ] Performance optimizations

**Deliverable**: Intelligence Release

---

## Phase 3: Scale (Q4 2026) - 12 Weeks

### Sprint 25-27: Additional Channels

**Priority**: P2

- [ ] Amazon integration
- [ ] eBay integration
- [ ] Noon integration

### Sprint 28-30: Advanced Features

**Priority**: P2

- [ ] Shipping management
- [ ] Fulfillment workflows
- [ ] Multi-warehouse support

### Sprint 31-33: Ecosystem

**Priority**: P2

- [ ] Extension marketplace
- [ ] Third-party integrations
- [ ] API expansion

### Sprint 34-36: Enterprise Features

**Priority**: P2

- [ ] Advanced permissions
- [ ] Audit logging
- [ ] Compliance tools
- [ ] Enterprise support tier

**Deliverable**: Enterprise Release

---

# Success Metrics

## Key Performance Indicators (KPIs)

### Product Metrics

| Metric | Current | Q2 Target | Q4 Target |
|--------|---------|-----------|-----------|
| Active Installations | ~100 | 500 | 2,000 |
| Multi-Channel Users | 0 | 100 | 500 |
| Avg SKUs per Instance | 1,000 | 5,000 | 10,000 |
| Channels Connected | 1 | 3 | 6 |

### Business Metrics

| Metric | Current | Q2 Target | Q4 Target |
|--------|---------|-----------|-----------|
| Enterprise Customers | 5 | 20 | 100 |
| ARR | $0 | $250K | $2M |
| Deal Size (ACV) | $60K | $100K | $180K |
| Customer Retention | N/A | 85% | 90% |

### Feature Adoption

| Feature | Q2 Target | Q4 Target |
|---------|-----------|-----------|
| Pricing Engine Usage | 50% | 80% |
| Budget Tracking Usage | 20% | 60% |
| Multi-Channel Sync | 30% | 70% |
| Analytics Dashboard | 60% | 90% |

---

# Risk Assessment

## Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| API rate limits from channels | High | Medium | Implement intelligent queuing |
| Data sync conflicts | High | Medium | Robust conflict resolution |
| Performance with large catalogs | High | Medium | Optimize queries, add caching |
| Integration API changes | Medium | High | Abstract integration layer |

## Business Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Market timing | High | Low | Fast iteration, early feedback |
| Competitor response | Medium | High | First-mover advantage, community |
| Customer adoption | High | Medium | Strong onboarding, documentation |
| Resource constraints | High | Medium | Phased approach, prioritize MVP |

---

# Dependencies

## Technical Dependencies

- [ ] Laravel 10.x framework (✅ already in place)
- [ ] PostgreSQL 14+ (✅ supported)
- [ ] Elasticsearch 8.x (✅ already integrated)
- [ ] Redis for caching (✅ already configured)
- [ ] Queue system for async processing (✅ Laravel Queue)

## External Dependencies

- [ ] Channel API access (Shopify Partner, Salla, Easy Orders)
- [ ] Marketing API access (Meta, Google)
- [ ] Shipping carrier APIs (future)

## Resource Requirements

### Development Team (Phase 1-2)

- 2 Backend Developers (Laravel)
- 2 Frontend Developers (Vue.js)
- 1 DevOps Engineer
- 1 QA Engineer
- 1 Product Manager
- 1 UI/UX Designer

### Development Team (Phase 3)

- Additional 2 Backend Developers
- Additional 1 Frontend Developer
- Additional 1 Integration Specialist

---

# Appendix

## A. User Personas

### Persona 1: E-commerce Manager (Primary)

- **Name**: Sarah
- **Role**: E-commerce Manager
- **Company**: Mid-size retailer (500-2000 SKUs)
- **Goals**:
  - Manage products across Shopify, Salla, Amazon
  - Optimize pricing for profitability
  - Track marketing spend
- **Pain Points**:
  - Too many spreadsheets
  - Manual price calculations
  - No visibility into true profitability

### Persona 2: Business Owner (Primary)

- **Name**: Ahmed
- **Role**: Founder/Owner
- **Company**: Small e-commerce business (100-500 SKUs)
- **Goals**:
  - Grow revenue profitably
  - Make data-driven decisions
  - Save time on operations
- **Pain Points**:
  - Don't know true profit per SKU
  - Can't compare channel performance
  - Spending too much time on manual work

### Persona 3: Operations Manager (Secondary)

- **Name**: Maria
- **Role**: Operations Manager
- **Company**: Large retailer (2000+ SKUs)
- **Goals**:
  - Optimize inventory allocation
  - Reduce operational costs
  - Improve fulfillment speed
- **Pain Points**:
  - Stock-outs on best sellers
  - Dead stock accumulation
  - Manual inventory allocation

## B. Glossary

- **COGS**: Cost of Goods Sold
- **CPP**: Cost Per Purchase
- **ROAS**: Return on Ad Spend
- **CAC**: Customer Acquisition Cost
- **AOV**: Average Order Value
- **SKU**: Stock Keeping Unit
- **PIM**: Product Information Management
- **PXM**: Product Experience Management
- **Run Rate**: Rate of sales over a period
- **Break-even**: Price at which profit = 0

## C. References

1. UnoPim Market Analysis Report (February 2026)
2. ECOM-OS Specification Document
3. Laravel Documentation
4. Shopify API Documentation
5. Salla API Documentation
6. Easy Orders API Documentation

---

**Document Status**: Draft v1.0  
**Last Updated**: February 14, 2026  
**Next Review**: February 21, 2026  
**Owner**: Product Team

---

*"List once. Price smart. Control budgets. Sell everywhere."*
