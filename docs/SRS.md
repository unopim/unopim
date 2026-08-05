# Software Requirements Specification (SRS)
# UnoPim — Open-Source Product Information Management System

**Version:** 1.0.0
**Date:** 2026-02-25
**Prepared by:** Reverse-engineered technical documentation
**License:** MIT

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [Stakeholders & User Classes](#3-stakeholders--user-classes)
4. [Functional Requirements](#4-functional-requirements)
5. [Non-Functional Requirements](#5-non-functional-requirements)
6. [Use Cases](#6-use-cases)
7. [Data Requirements](#7-data-requirements)
8. [External Interface Requirements](#8-external-interface-requirements)
9. [Constraints & Assumptions](#9-constraints--assumptions)
10. [Appendix: Glossary](#10-appendix-glossary)

---

## 1. Introduction

### 1.1 Purpose

This Software Requirements Specification (SRS) defines the functional and non-functional requirements for **UnoPim**, an open-source Product Information Management (PIM) system. UnoPim enables businesses to centralize, enrich, and distribute product data across multiple sales channels and locales.

### 1.2 Scope

UnoPim covers:
- Centralized product catalog management with dynamic attribute modeling
- Hierarchical category management
- Multi-channel and multi-locale data distribution
- Role-based access control for multiple admin users
- Bulk data import/export via CSV and Excel
- REST API for external system integration
- AI-powered content generation (MagicAI)
- Webhook-based event notifications
- Elasticsearch-powered search
- Product data completeness scoring

Out of scope:
- Storefront/e-commerce checkout
- Customer-facing order management
- Payment processing

### 1.3 Definitions & Acronyms

| Term | Definition |
|------|-----------|
| PIM | Product Information Management |
| SKU | Stock Keeping Unit — unique product identifier |
| Attribute Family | A named group of attributes assigned to a product type |
| Attribute Group | A logical sub-grouping of attributes within a family |
| Channel | A sales/distribution outlet (e.g., web store, marketplace) |
| Locale | A language/region combination (e.g., en_US, fr_FR) |
| Completeness Score | % of required attributes filled for a product |
| RBAC | Role-Based Access Control |
| Webhook | HTTP callback triggered by system events |

### 1.4 References

- Laravel 10.x Documentation
- Elasticsearch 8.17 Reference
- Laravel Passport OAuth2 Documentation
- OpenAI API Reference

---

## 2. Overall Description

### 2.1 Product Perspective

UnoPim is a standalone web application that acts as a **single source of truth** for product data. It integrates with:
- E-commerce platforms (via API or webhooks)
- ERP systems (via Import/Export)
- AI services (OpenAI for content enrichment)
- Search engines (Elasticsearch)
- Real-time systems (Pusher for notifications)

```
┌──────────────────────────────────────────────────┐
│                   External Systems                │
│  ERP │ E-Commerce │ Marketplace │ AI (OpenAI)    │
└────────┬──────────────┬────────────────────────┬─┘
         │ Import/Export│ REST API / Webhooks     │ AI
┌────────▼──────────────▼────────────────────────▼─┐
│                     UnoPim                        │
│  Product Catalog │ Categories │ Attributes        │
│  Channels │ Locales │ Users │ Search              │
└──────────────────────────────────────────────────┘
         │ Database │ Cache │ Queue
┌────────▼──────────▼───────▼──────────────────────┐
│   MySQL/PostgreSQL │ Redis │ Elasticsearch         │
└──────────────────────────────────────────────────┘
```

### 2.2 Product Functions (Summary)

1. **Product Management** — Create, read, update, delete products with typed attributes
2. **Category Management** — Hierarchical nested-set categories
3. **Attribute System** — Dynamic attribute types (text, select, image, price, etc.)
4. **Attribute Families** — Templates defining which attributes a product has
5. **Multi-channel** — Assign products/data to multiple sales channels
6. **Multi-locale** — Translate product data into multiple languages
7. **Import/Export** — Bulk CSV/XLSX data transfer with job-based processing
8. **REST API** — OAuth2-secured API for all operations
9. **Webhooks** — Event-driven HTTP callbacks for system integrations
10. **Search** — Elasticsearch for high-performance product search
11. **MagicAI** — AI content generation and translation
12. **Completeness** — Per-channel/locale data quality scoring
13. **History Control** — Full audit trail of changes to products and categories
14. **Notifications** — Real-time user notifications

### 2.3 Operating Environment

| Component | Requirement |
|-----------|------------|
| PHP | 8.2+ |
| Web Server | Apache / Nginx |
| Database | MySQL 8.0+ or PostgreSQL 14+ |
| Cache | Redis (recommended) or File |
| Queue | Redis (recommended) or Database |
| Search | Elasticsearch 8.17 (optional) |
| Node.js | 18+ (for asset compilation) |
| OS | Linux (Ubuntu 22.04+ recommended) |

---

## 3. Stakeholders & User Classes

### 3.1 User Classes

#### Super Administrator
- Full access to all system features
- Manages users, roles, channels, locales, currencies
- Configures system-wide settings

#### Product Manager
- Creates and enriches product data
- Manages categories and attribute families
- Runs import/export operations
- Uses MagicAI for content generation

#### Data Entry Operator
- Fills in product attributes
- Assigns products to categories and channels
- Limited access based on role permissions

#### API Consumer (External System)
- Uses REST API via OAuth2 tokens
- Reads/writes product data programmatically
- Receives webhook notifications

#### Read-Only Viewer
- Browses products and categories
- No write permissions

### 3.2 Stakeholders

| Stakeholder | Interest |
|-------------|----------|
| Business Owner | Product data accuracy, time-to-market |
| IT Team | System reliability, integration capability |
| Marketing Team | Rich content, multi-channel publishing |
| E-Commerce Team | Accurate product feeds to storefronts |
| Data Analysts | Completeness reporting, export capabilities |

---

## 4. Functional Requirements

### 4.1 Authentication & Authorization

**FR-AUTH-01:** The system shall provide session-based authentication for admin users.
**FR-AUTH-02:** The system shall provide OAuth2 token-based authentication via Laravel Passport for API consumers.
**FR-AUTH-03:** The system shall support password reset via email token.
**FR-AUTH-04:** The system shall enforce Role-Based Access Control (RBAC) with configurable permissions per role.
**FR-AUTH-05:** The system shall support API key authentication as an alternative to OAuth2 tokens.
**FR-AUTH-06:** Authenticated sessions shall expire after a configurable inactivity period.
**FR-AUTH-07:** The system shall apply rate limiting of 60 requests/minute per user/IP on API routes.

### 4.2 Product Management

**FR-PROD-01:** The system shall support creation of products with a unique SKU identifier.
**FR-PROD-02:** The system shall support two product types: **Simple** and **Configurable** (parent-child variants).
**FR-PROD-03:** Each product shall be associated with exactly one Attribute Family.
**FR-PROD-04:** Products shall support the following attribute value types:
  - Text (plain and WYSIWYG)
  - Textarea
  - Boolean
  - Price (with currency)
  - Select (single option)
  - Multiselect (multiple options)
  - DateTime and Date
  - Checkbox
  - File upload
  - Image upload
  - Image Gallery

**FR-PROD-05:** Product attribute values shall be scoped per channel and/or locale.
**FR-PROD-06:** The system shall support bulk editing of multiple products simultaneously.
**FR-PROD-07:** Products shall have an enable/disable status.
**FR-PROD-08:** Product changes shall be tracked in an auditable history log.
**FR-PROD-09:** The system shall enforce required attribute validation based on family definition.
**FR-PROD-10:** Configurable products shall support super attributes defining variant dimensions (e.g., size, color).

### 4.3 Category Management

**FR-CAT-01:** Categories shall be organized in a hierarchical tree structure (unlimited depth).
**FR-CAT-02:** Each category shall have a unique code identifier.
**FR-CAT-03:** Categories shall support custom fields (CategoryField) with configurable types.
**FR-CAT-04:** Category data shall support multi-locale translations.
**FR-CAT-05:** Products shall be assignable to multiple categories.
**FR-CAT-06:** Category changes shall be tracked in an auditable history log.
**FR-CAT-07:** The system shall provide efficient tree queries without recursive SQL using the Nested Set model.

### 4.4 Attribute System

**FR-ATTR-01:** Administrators shall be able to define custom attributes with a unique code.
**FR-ATTR-02:** Attributes shall support the value types listed in FR-PROD-04.
**FR-ATTR-03:** Attributes shall be grouped into Attribute Groups for organizational display.
**FR-ATTR-04:** Attribute Groups shall be organized into Attribute Families (templates).
**FR-ATTR-05:** Select/Multiselect attributes shall have configurable options.
**FR-ATTR-06:** Attributes shall be configurable as required, localizable, or channel-scoped.
**FR-ATTR-07:** Attribute names shall be translatable across locales.
**FR-ATTR-08:** Attributes shall support swatch display types for visual options.

### 4.5 Multi-Channel Management

**FR-CHAN-01:** The system shall support multiple channels (e.g., web store, mobile app, marketplace).
**FR-CHAN-02:** Each channel shall have configurable root category, locales, and currencies.
**FR-CHAN-03:** Product attribute values shall be independently configurable per channel.
**FR-CHAN-04:** Product completeness scoring shall be computed per channel and per locale.

### 4.6 Multi-Locale & Currency Management

**FR-LOCALE-01:** The system shall support multiple locales simultaneously.
**FR-LOCALE-02:** Product attribute values flagged as localizable shall store separate values per locale.
**FR-LOCALE-03:** The system shall support multiple currencies with configurable exchange rates.
**FR-LOCALE-04:** Price attributes shall store values per currency.
**FR-LOCALE-05:** The admin UI shall be translatable via the locale system (27 supported locales for validation messages).

### 4.7 Import & Export

**FR-IE-01:** The system shall support importing products via CSV and XLSX file formats.
**FR-IE-02:** The system shall support importing categories via CSV and XLSX file formats.
**FR-IE-03:** The system shall support exporting products to CSV and XLSX formats.
**FR-IE-04:** The system shall support exporting categories to CSV and XLSX formats.
**FR-IE-05:** Import and export operations shall be processed asynchronously via the job queue.
**FR-IE-06:** Import jobs shall support batch processing (100 records per batch) for memory efficiency.
**FR-IE-07:** Failed import rows shall be logged with error details.
**FR-IE-08:** The system shall display import/export job progress to the user.
**FR-IE-09:** Import jobs shall have an unlimited timeout to support large datasets.

### 4.8 REST API

**FR-API-01:** The system shall expose a RESTful API for all major product data operations.
**FR-API-02:** API access shall require valid OAuth2 Bearer token or API key.
**FR-API-03:** The API shall support CRUD operations on products, categories, attributes, and families.
**FR-API-04:** The API shall return paginated responses for list endpoints.
**FR-API-05:** The API shall support filtering and sorting on list endpoints.
**FR-API-06:** API rate limits shall be enforced (60 requests/minute per user).

### 4.9 Webhooks

**FR-WH-01:** Administrators shall be able to configure webhook URLs for system events.
**FR-WH-02:** The system shall dispatch HTTP POST requests to registered webhook URLs when configured events occur.
**FR-WH-03:** Webhook dispatching shall be processed asynchronously via the job queue.
**FR-WH-04:** The system shall support configurable event types per webhook.

### 4.10 Search

**FR-SRCH-01:** The system shall support full-text product search via Elasticsearch when configured.
**FR-SRCH-02:** The system shall fall back to database-powered search when Elasticsearch is not available.
**FR-SRCH-03:** The search index shall be automatically updated when products are created, updated, or deleted.
**FR-SRCH-04:** Search shall support filtering by channel, locale, and attribute values.

### 4.11 MagicAI

**FR-AI-01:** The system shall integrate with the OpenAI API for content generation.
**FR-AI-02:** Users shall be able to trigger AI-generated content for product description fields.
**FR-AI-03:** Users shall be able to configure AI system prompts per field or use case.
**FR-AI-04:** AI shall support translating product attribute values across locales.

### 4.12 Data Completeness

**FR-COMP-01:** The system shall calculate a completeness score per product, per channel, per locale.
**FR-COMP-02:** Completeness scores shall reflect the percentage of required attributes that have values.
**FR-COMP-03:** Completeness scores shall be recalculated asynchronously when product data changes.

### 4.13 History & Audit

**FR-HIST-01:** The system shall maintain a full change history for products.
**FR-HIST-02:** The system shall maintain a full change history for categories.
**FR-HIST-03:** History records shall include the changed field, old value, new value, actor, and timestamp.
**FR-HIST-04:** Administrators shall be able to view and compare historical versions.

### 4.14 Notifications

**FR-NOTIF-01:** The system shall deliver in-app notifications to admin users for relevant events.
**FR-NOTIF-02:** Notifications shall support marking as read individually or in bulk.
**FR-NOTIF-03:** Optional real-time delivery via Pusher WebSockets.

### 4.15 DataGrid

**FR-DG-01:** All list views (products, categories, attributes, etc.) shall be rendered via a DataGrid component.
**FR-DG-02:** DataGrid shall support column-level filtering, sorting, and search.
**FR-DG-03:** DataGrid shall support configurable column visibility per user.
**FR-DG-04:** DataGrid shall support pagination with configurable page size.
**FR-DG-05:** DataGrid shall support bulk actions (delete, status change, etc.).

---

## 5. Non-Functional Requirements

### 5.1 Performance

**NFR-PERF-01:** Product list pages shall load within 2 seconds for datasets up to 1 million products.
**NFR-PERF-02:** Individual product save operations shall complete within 1 second under normal load.
**NFR-PERF-03:** Import jobs shall process at minimum 1,000 products per minute.
**NFR-PERF-04:** Elasticsearch-powered searches shall return results within 200ms.
**NFR-PERF-05:** DataGrid queries shall use indexed columns and pagination to prevent full-table scans.

### 5.2 Scalability

**NFR-SCALE-01:** The system shall support catalogs of 1 million+ products without architectural changes.
**NFR-SCALE-02:** The queue system shall scale horizontally by adding queue workers.
**NFR-SCALE-03:** The cache layer (Redis) shall reduce database load for frequently accessed data.
**NFR-SCALE-04:** Elasticsearch shall handle full-text search at scale, decoupling search load from the database.
**NFR-SCALE-05:** Database queries in DataGrid shall use cursor pagination for large result sets.

### 5.3 Reliability

**NFR-REL-01:** The system shall log all failed queue jobs for manual inspection and retry.
**NFR-REL-02:** The system shall maintain a failed_jobs table for job recovery.
**NFR-REL-03:** Import operations shall be atomic at the batch level — a failed batch shall not corrupt previous successful batches.
**NFR-REL-04:** The system shall provide maintenance mode without data loss.

### 5.4 Security

**NFR-SEC-01:** All HTTP responses shall include secure headers (X-Frame-Options, Content-Security-Policy, etc.).
**NFR-SEC-02:** All forms shall be protected by CSRF tokens.
**NFR-SEC-03:** API tokens shall expire after a configurable TTL (default: 3600 seconds).
**NFR-SEC-04:** Passwords shall be hashed using bcrypt.
**NFR-SEC-05:** Input validation shall be applied at all API endpoints and form submissions.
**NFR-SEC-06:** File uploads shall be validated for type and size.

### 5.5 Usability

**NFR-USE-01:** The admin interface shall be responsive across desktop (1024px+) and tablet (768px+) screens.
**NFR-USE-02:** The system shall support dark mode via CSS class toggling.
**NFR-USE-03:** Loading states shall be indicated with shimmer animations.
**NFR-USE-04:** Form validation errors shall be displayed inline next to the relevant field.
**NFR-USE-05:** All user actions shall receive feedback via flash/toast notifications.

### 5.6 Maintainability

**NFR-MAINT-01:** The system shall follow modular architecture using Konekt Concord, allowing modules to be added or removed independently.
**NFR-MAINT-02:** All modules shall use the Repository Pattern to abstract database access.
**NFR-MAINT-03:** The codebase shall follow PSR-12 PHP coding standards.
**NFR-MAINT-04:** Each module shall be independently testable.

### 5.7 Internationalisation

**NFR-I18N-01:** The system shall support right-to-left (RTL) locales at the layout level.
**NFR-I18N-02:** All user-facing strings shall be translatable via Laravel's localization system.
**NFR-I18N-03:** Date and currency formatting shall adapt to the selected locale.

---

## 6. Use Cases

### UC-01: Create a Product

**Actor:** Product Manager
**Preconditions:** At least one Attribute Family exists; user is authenticated
**Flow:**
1. User navigates to Catalog > Products > Add Product
2. User selects an Attribute Family
3. System renders attribute groups and fields for the selected family
4. User fills in SKU, attribute values per locale/channel
5. User saves the product
6. System validates required fields and formats
7. System persists the product and dispatches completeness calculation job
8. System displays success notification
**Postconditions:** Product is saved; completeness score is queued for calculation
**Exceptions:** SKU already exists → validation error; required field missing → validation error

### UC-02: Bulk Import Products

**Actor:** Data Entry Operator / Product Manager
**Preconditions:** CSV/XLSX file prepared with valid column headers
**Flow:**
1. User navigates to Data Transfer > Import
2. User selects "Products" entity type
3. User uploads CSV/XLSX file
4. System queues import job
5. Queue worker processes file in batches of 100
6. System updates import progress in real time
7. System notifies user upon completion with success/failure summary
**Postconditions:** Products created or updated; failed rows logged
**Exceptions:** Invalid file format → immediate error; duplicate SKUs → row-level error logged

### UC-03: Generate AI Product Description

**Actor:** Product Manager
**Preconditions:** OpenAI API key configured; product exists
**Flow:**
1. User opens a product edit page
2. User clicks the MagicAI button on a textarea/text attribute
3. System sends product context + system prompt to OpenAI API
4. System populates the attribute field with generated content
5. User reviews and saves
**Postconditions:** Attribute value populated with AI content
**Exceptions:** OpenAI API error → error message displayed

### UC-04: Configure a Webhook

**Actor:** Super Administrator
**Preconditions:** External system URL available
**Flow:**
1. User navigates to Settings > Webhooks
2. User creates a new webhook with URL and event types
3. System saves the webhook configuration
4. When a registered event fires, system dispatches a webhook job
5. Queue worker sends HTTP POST to the configured URL
**Postconditions:** External system receives event notification
**Exceptions:** HTTP POST fails → job retried per queue configuration

### UC-05: Manage Attribute Families

**Actor:** Super Administrator
**Preconditions:** Attributes exist
**Flow:**
1. User creates an Attribute Family with a name/code
2. User adds Attribute Groups to the family
3. User assigns existing Attributes to each group
4. User saves the family
5. Products can now be created with this family
**Postconditions:** Family available for product creation
**Exceptions:** Duplicate family code → validation error

---

## 7. Data Requirements

### 7.1 Core Entities

| Entity | Key Fields | Notes |
|--------|-----------|-------|
| Product | id, sku, type, attribute_family_id, parent_id, status, values (JSON) | Polymorphic type system |
| Category | id, code, parent_id, lft, rgt, depth, additional_data (JSON) | Nested Set tree |
| Attribute | id, code, type, is_required, is_localizable, is_scopable | Dynamic attribute system |
| AttributeFamily | id, code, name | Groups attributes for products |
| AttributeGroup | id, code, name, attribute_family_id | Sub-groups within families |
| AttributeOption | id, attribute_id, code | Options for select/multiselect |
| Channel | id, code, name, root_category_id | Multi-channel support |
| Locale | id, code, name, direction | Language support |
| Currency | id, code, name, symbol, exchange_rate | Multi-currency |
| Admin | id, name, email, password, role_id, status | System users |
| Role | id, name, permissions (JSON) | RBAC roles |
| Webhook | id, url, events, status | Outbound webhooks |
| JobBatch | id, name, total, processed, failed | Batch job tracking |
| Audit | id, user_type, user_id, event, auditable_type, auditable_id, old_values, new_values | Audit trail |

### 7.2 Data Volumes

| Entity | Expected Scale |
|--------|---------------|
| Products | Up to 10 million |
| Category tree nodes | Up to 100,000 |
| Attributes | Up to 1,000 |
| Attribute options | Up to 100,000 |
| Locales | Up to 50 |
| Channels | Up to 20 |
| Import jobs per day | Up to 100 large batches |
| API requests per day | Up to 10 million |

---

## 8. External Interface Requirements

### 8.1 User Interfaces

- Admin web interface: Desktop browser (Chrome 100+, Firefox 100+, Safari 15+, Edge 100+)
- Minimum supported viewport: 768px width
- Dark mode supported via OS preference or manual toggle
- Responsive layout with collapsible sidebar (270px → 70px)

### 8.2 API Interface

- Protocol: HTTPS REST
- Authentication: OAuth2 Bearer Token (Laravel Passport) or API Key header
- Response format: JSON
- Pagination: Cursor-based or offset-based
- Rate limit: 60 requests/minute
- Versioned endpoints under `/api/v1/`

### 8.3 Import/Export Interface

- Supported formats: CSV, XLSX
- Encoding: UTF-8
- Max file size: Configurable (PHP upload_max_filesize)
- Column headers match attribute codes

### 8.4 Webhook Interface

- Method: HTTP POST
- Payload format: JSON
- Includes: event name, entity type, entity ID, timestamp, changed data
- Retry policy: Per queue configuration (default: up to 3 retries)

### 8.5 External Services

| Service | Integration | Required |
|---------|------------|----------|
| Elasticsearch | Product search | Optional |
| Redis | Cache + Queue | Recommended |
| OpenAI API | MagicAI content generation | Optional |
| Pusher | Real-time notifications | Optional |
| SMTP Server | Password reset emails | Required |

---

## 9. Constraints & Assumptions

### 9.1 Constraints

- The system is designed as an admin-only web application; no customer-facing storefront is included.
- File-based queue (sync driver) is not recommended for production use with large catalogs.
- Elasticsearch is optional but strongly recommended for catalogs with 100,000+ products.
- The installer must be run once before using the application.

### 9.2 Assumptions

- The host environment has PHP 8.2+ with required extensions (PDO, GD, BCMath, Ctype, JSON, Mbstring, OpenSSL, Tokenizer, XML).
- The host has a running MySQL 8.0+ or PostgreSQL 14+ database server.
- Redis is available for production deployments.
- The deployment team is familiar with Laravel application deployment practices.

---

## 10. Appendix: Glossary

| Term | Definition |
|------|-----------|
| Attribute Family | A template that defines which attributes a product type has, organized into groups |
| Channel | A configured distribution outlet with its own root category, locales, and currencies |
| Completeness Score | A percentage (0–100%) indicating how many required attributes have values for a given product/channel/locale combination |
| Configurable Product | A parent product with child variants defined by super attributes (e.g., a T-Shirt with Size and Color variants) |
| DataGrid | A server-rendered, filterable, sortable, paginated data table component used throughout the admin |
| Locale | A language/country combination that determines how content is displayed and stored |
| MagicAI | The OpenAI-powered content generation module within UnoPim |
| Nested Set | A database technique for efficiently storing and querying hierarchical tree structures |
| Repository Pattern | An abstraction layer between the application logic and the data access layer (Eloquent ORM) |
| Simple Product | A standalone product with no variants |
| SKU | Stock Keeping Unit — a unique identifier for a product |
| Webhook | A user-configured HTTP endpoint that receives event notifications from UnoPim |
