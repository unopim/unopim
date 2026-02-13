# UnoPim - Data Models

## Overview

UnoPim uses ~30+ database tables organized across 12 Webkul packages with ~61 migration files. The data model supports multi-channel, multi-locale product information with flexible attributes, hierarchical categories, and comprehensive auditing.

---

## Entity Relationship Summary

```
Admin ──────→ Role (many:1)
  │
  ├──→ Apikey ──→ OAuthClient (1:1)
  └──→ UserNotification ──→ Notification

Channel ←──→ Locale (many:many via channel_locales)
Channel ←──→ Currency (many:many via channel_currencies)
Channel ──→ Category (root_category, 1:1)

Product ──→ AttributeFamily (many:1)
Product ──→ Product (parent/variants, self-ref)
Product ←──→ Attribute (super_attributes, many:many)
Product ──→ ProductCompletenessScore (1:many)

AttributeFamily ←──→ AttributeGroup (many:many via mappings)
AttributeGroup ←──→ Attribute (many:many via mappings)
Attribute ──→ AttributeOption (1:many)

Category ──→ Category (nested set, self-ref)
CategoryField ──→ CategoryFieldOption (1:many)

JobInstances ──→ JobTrack (1:many)
JobTrack ──→ JobTrackBatch (1:many)

CompletenessSetting ──→ AttributeFamily, Attribute, Channel
```

---

## Core Tables

### admins
**Package:** User | **Model:** `Webkul\User\Models\Admin`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| name | string | Full name |
| email | string (unique) | Email address |
| password | string (hidden) | BCrypt hashed |
| image | string (nullable) | Profile image path |
| api_token | string (hidden) | Legacy API token |
| role_id | bigint (FK) | References roles.id |
| ui_locale_id | bigint (FK) | References locales.id |
| status | boolean | Active/inactive |
| timezone | string (nullable) | User timezone |
| remember_token | string (hidden) | Session persistence |

**Relationships:** belongsTo(Role), belongsTo(Locale), hasOne(Apikey), hasMany(UserNotification)
**Traits:** HasApiTokens, Notifiable, Auditable, HasFactory

### roles
**Package:** User | **Model:** `Webkul\User\Models\Role`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| name | string | Role name |
| description | string (nullable) | Role description |
| permission_type | string | 'all' or 'custom' |
| permissions | json | Array of permission keys |

**Casts:** permissions → array
**Relationships:** hasMany(Admin)

### locales
**Package:** Core | **Model:** `Webkul\Core\Models\Locale`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Locale code (e.g., en_US) |
| status | boolean | Enabled/disabled |

**Computed:** name (from Symfony Intl)
**Relationships:** belongsToMany(Channel)

### currencies
**Package:** Core | **Model:** `Webkul\Core\Models\Currency`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Currency code (e.g., USD) |
| symbol | string (nullable) | Currency symbol |
| decimal | int | Decimal places |
| status | boolean | Enabled/disabled |

**Computed:** name (from Symfony Intl)
**Relationships:** belongsToMany(Channel), hasOne(CurrencyExchangeRate)

### channels
**Package:** Core | **Model:** `Webkul\Core\Models\Channel`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Channel code |
| name | string | Channel name |
| root_category_id | bigint (FK) | References categories.id |

**Translatable:** name
**Pivot Tables:** channel_locales, channel_currencies, channel_translations
**Relationships:** belongsToMany(Locale), belongsToMany(Currency), belongsTo(Category)

### core_config
**Package:** Core | **Model:** `Webkul\Core\Models\CoreConfig`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string | Configuration key |
| value | text (nullable) | Configuration value |
| channel_code | string (nullable) | Channel scope |
| locale_code | string (nullable) | Locale scope |

---

## Attribute System

### attributes
**Package:** Attribute | **Model:** `Webkul\Attribute\Models\Attribute`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Attribute code |
| type | string | text, textarea, boolean, price, select, multiselect, datetime, date, checkbox, file, image, gallery |
| enable_wysiwyg | boolean | Rich text editor |
| position | int | Sort order |
| swatch_type | string (nullable) | Visual swatch type |
| is_required | boolean | Required validation |
| is_unique | boolean | Uniqueness constraint |
| validation | string (nullable) | Validation rule |
| regex_pattern | string (nullable) | Custom regex |
| value_per_locale | boolean | Locale-specific values |
| value_per_channel | boolean | Channel-specific values |
| is_filterable | boolean | Datagrid filter support |
| ai_translate | boolean | AI translation eligible |

**Translatable:** name (via attribute_translations)
**Relationships:** hasMany(AttributeOption)
**Protected:** SKU attribute cannot be deleted

### attribute_options
**Package:** Attribute | **Model:** `Webkul\Attribute\Models\AttributeOption`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string | Option code |
| swatch_value | string (nullable) | Swatch value (color/image) |
| sort_order | int | Sort order |
| attribute_id | bigint (FK) | References attributes.id |

**Translatable:** label (via attribute_option_translations)
**Relationships:** belongsTo(Attribute)

### attribute_families
**Package:** Attribute | **Model:** `Webkul\Attribute\Models\AttributeFamily`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Family code |

**Translatable:** name (via attribute_family_translations)
**Relationships:** hasMany(AttributeFamilyGroupMapping), belongsToMany(AttributeGroup), hasMany(Product)
**Timestamps:** false

### attribute_groups
**Package:** Attribute | **Model:** `Webkul\Attribute\Models\AttributeGroup`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Group code |
| column | int | Display column |
| position | int | Sort order |

**Translatable:** name (via attribute_group_translations)
**Timestamps:** false

### attribute_family_group_mappings
**Package:** Attribute | **Model:** `Webkul\Attribute\Models\AttributeFamilyGroupMapping`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| attribute_family_id | bigint (FK) | References attribute_families.id |
| attribute_group_id | bigint (FK) | References attribute_groups.id |
| position | int | Sort order |

### attribute_group_mappings
Pivot table linking attributes to family group mappings.

| Column | Type | Description |
|--------|------|-------------|
| attribute_id | bigint (PK, FK) | References attributes.id |
| attribute_family_group_id | bigint (PK, FK) | References attribute_family_group_mappings.id |
| position | int | Sort order |

---

## Product System

### products
**Package:** Product | **Model:** `Webkul\Product\Models\Product`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| type | string | 'simple' or 'configurable' |
| attribute_family_id | bigint (FK) | References attribute_families.id |
| sku | string (unique) | Stock Keeping Unit |
| parent_id | bigint (FK, nullable) | Self-reference for variants |
| status | boolean | Published/draft |
| values | json | All attribute values (see JSON structure below) |
| additional | json | Additional metadata |

**JSON `values` Structure:**
```json
{
  "common": { "sku": "...", "status": true },
  "locale_specific": { "en_US": { "name": "..." } },
  "channel_specific": { "default": { "price": 29.99 } },
  "channel_locale_specific": { "default": { "en_US": { "meta_title": "..." } } }
}
```

**Relationships:** belongsTo(Product as parent), belongsTo(AttributeFamily), belongsToMany(Attribute via product_super_attributes), hasMany(Product as variants), hasMany(ProductCompletenessScore)
**Traits:** HasFactory, Visitable, HistoryTrait

### product_super_attributes
| Column | Type | Description |
|--------|------|-------------|
| product_id | bigint (FK, unique with attribute_id) | References products.id |
| attribute_id | bigint (FK) | References attributes.id |

### product_relations
| Column | Type | Description |
|--------|------|-------------|
| parent_id | bigint (FK, unique with child_id) | References products.id |
| child_id | bigint (FK) | References products.id |

---

## Category System

### categories
**Package:** Category | **Model:** `Webkul\Category\Models\Category`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Category code |
| parent_id | bigint (FK, nullable) | References categories.id |
| _lft | int | Nested set left boundary |
| _rgt | int | Nested set right boundary |
| additional_data | json | Category field values (locale-specific) |

**Structure:** Nested Set Model (kalnoy/nestedset)
**Relationships:** belongsTo(Category as parent)
**Traits:** NodeTrait, Visitable, HistoryTrait

### category_fields
**Package:** Category | **Model:** `Webkul\Category\Models\CategoryField`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string (unique) | Field code |
| type | string | text, textarea, boolean, select, multiselect, datetime, date, file, image, checkbox |
| enable_wysiwyg | boolean | Rich text support |
| position | int | Sort order |
| status | boolean | Enabled/disabled |
| section | string | Field section grouping |
| is_required | boolean | Required validation |
| is_unique | boolean | Uniqueness constraint |
| validation | string (nullable) | Validation rule |
| value_per_locale | boolean | Locale-specific values |
| regex_pattern | string (nullable) | Custom regex |

**Translatable:** name (via category_field_translations)
**Protected:** name field (code='name') cannot be deleted

### category_field_options
| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string | Option code |
| sort_order | int | Sort order |
| category_field_id | bigint (FK) | References category_fields.id |

**Translatable:** label (via category_field_option_translations)

---

## API & Authentication

### api_keys
**Package:** AdminApi | **Model:** `Webkul\AdminApi\Models\Apikey`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| name | string | API key name |
| admin_id | bigint (FK) | References admins.id |
| oauth_client_id | uuid (FK) | References oauth_clients.id |
| permission_type | string | 'all' or 'custom' |
| permissions | json | Array of permission keys |
| revoked | boolean | Revocation status |

### OAuth Tables (Laravel Passport)
- `oauth_clients` - OAuth client credentials
- `oauth_access_tokens` - Access tokens
- `oauth_auth_codes` - Authorization codes
- `oauth_refresh_tokens` - Refresh tokens
- `oauth_personal_access_clients` - Personal access clients
- `personal_access_tokens` - Sanctum tokens

---

## Data Transfer

### job_instances
**Package:** DataTransfer | **Model:** `Webkul\DataTransfer\Models\JobInstances`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| code | string | Job identifier |
| entity_type | string | products, categories, etc. |
| type | string | import or export |
| action | string | Processing action |
| validation_strategy | string | Validation approach |
| allowed_errors | int | Error threshold |
| field_separator | string | CSV delimiter |
| file_path | string | Source/destination file |
| images_directory_path | string (nullable) | Image directory |
| filters | json | Export filters |

### job_track
**Model:** `Webkul\DataTransfer\Models\JobTrack`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| state | string | pending, validated, processing, completed, failed |
| processed_rows_count | int | Rows processed |
| invalid_rows_count | int | Invalid rows |
| errors_count | int | Error count |
| errors | json | Error details |
| summary | json | Processing summary |
| started_at | datetime | Job start time |
| completed_at | datetime | Job completion time |
| meta | json | Additional metadata |
| job_instances_id | bigint (FK) | References job_instances.id |
| user_id | bigint (FK) | References admins.id |

### job_track_batches
| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| state | string | Batch state |
| data | json | Batch data payload |
| summary | json | Batch summary |
| job_track_id | bigint (FK) | References job_track.id |

---

## Completeness

### completeness_settings
**Package:** Completeness | **Model:** `Webkul\Completeness\Models\CompletenessSetting`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| family_id | bigint (FK) | References attribute_families.id |
| attribute_id | bigint (FK) | References attributes.id |
| channel_id | bigint (FK) | References channels.id |

### product_completeness
**Model:** `Webkul\Completeness\Models\ProductCompletenessScore`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| product_id | bigint (FK) | References products.id |
| channel_id | bigint (FK) | References channels.id |
| locale_id | bigint (FK) | References locales.id |
| score | decimal | Completeness percentage |
| missing_count | int | Missing required attributes |

---

## Notifications

### notifications
**Package:** Notification | **Model:** `Webkul\Notification\Models\Notification`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| type | string | Notification type |
| route | string | Navigation route |
| route_params | json | Route parameters |
| title | string | Notification title |
| description | text | Notification body |
| context | json | Additional context |

### user_notifications
| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| admin_id | bigint (FK) | References admins.id |
| notification_id | bigint (FK) | References notifications.id |
| read | boolean | Read status |

---

## Webhooks

### webhook_settings
**Package:** Webhook | **Model:** `Webkul\Webhook\Models\WebhookSetting`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| field | string | Setting field name |
| value | text | Setting value |
| extra | json | Additional configuration |

### webhook_logs
**Model:** `Webkul\Webhook\Models\WebhookLog`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| sku | string | Product SKU |
| user | string | Triggering user |
| status | string | Delivery status |
| extra | json | Request/response details |

---

## AI Features

### magic_ai_prompts
**Package:** MagicAI | **Model:** `Webkul\MagicAI\Models\MagicPrompt`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| prompt | text | AI prompt template |
| title | string | Prompt title |
| type | string | Prompt type |
| tone | string | Writing tone |

### magic_ai_system_prompts
**Model:** `Webkul\MagicAI\Models\MagicAISystemPrompt`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| title | string | System prompt title |
| tone | string | Writing tone |
| max_tokens | int | Token limit |
| temperature | decimal | Creativity setting |
| is_enabled | boolean | Active status (only one active) |

---

## History & Auditing

### histories
**Package:** HistoryControl | **Model:** `Webkul\HistoryControl\Models\History`

Tracks version control for entities with comprehensive change tracking.

### audits
**Package:** OwenIt\Auditing (vendor)

Standard audit trail table with `version_id` and `history_id` extensions for UnoPim's versioning system.

---

## System Tables (Laravel)

| Table | Purpose |
|-------|---------|
| admin_password_resets | Password reset tokens |
| jobs | Queue job storage |
| failed_jobs | Failed job tracking |
| job_batches | Batch job coordination |
| migrations | Migration tracking |
