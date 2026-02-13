# UnoPim - DOMAIN Layer Patterns & Skills

> Reference documentation for the Domain architectural layer.
> Generated from exhaustive codebase scan - 2026-02-08

---

## Table of Contents

- [Product Domain](#product-domain)
- [Attribute Domain](#attribute-domain)
- [Category Domain](#category-domain)
- [User/Auth Domain](#userauth-domain)
- [DataTransfer Domain](#datatransfer-domain)
- [MagicAI Domain](#magicai-domain)
- [Cross-Domain Patterns](#cross-domain-patterns)
- [Architecture Summary](#architecture-summary)

---

## Product Domain

**Package:** `packages/Webkul/Product/src/`

### Model Hierarchy

#### Core Model: `Product`

- **Extends:** `Illuminate\Database\Eloquent\Model`
- **Implements:** `ProductContract`, `HistoryAuditable`, `PresentableHistoryInterface`
- **Traits:** `HasFactory`, `Visitable`, `HistoryTrait`
- **Fillable:** `type`, `attribute_family_id`, `sku`, `parent_id`, `status`
- **Casts:** `additional` (array), `values` (array)

#### Relations

| Relation | Type | Target | Notes |
|---|---|---|---|
| `parent()` | BelongsTo | `Product` | Self-referencing for variants |
| `attribute_family()` | BelongsTo | `AttributeFamily` | |
| `super_attributes()` | BelongsToMany | `Attribute` | Via `product_super_attributes` |
| `images()` | HasMany | `ProductImage` | Ordered by position |
| `variants()` | HasMany | `Product` | Self-referencing for configurable products |
| `completenessScores()` | HasMany | `ProductCompletenessScore` | |

#### Key Methods

- `getTypeInstance()` -- Returns configured product type handler (Simple, Configurable, etc.)
- `getAttribute($key)` -- Custom attribute accessor supporting family attributes
- `getEditableAttributes($group, $skipSuperAttribute)` -- Returns editable attributes via type instance
- `getCompletenessScore($channelId, $select)` -- Returns completeness by locale
- `getCompletenessAttributes($channelId)` -- Completeness settings for channel
- `getCustomAttributeValue($attribute)` -- Resolves attribute value by channel/locale scope
- `checkInLoadedFamilyAttributes()` -- Loads family attributes from singleton AttributeRepository
- `getProductDisplayImage()` -- Resolves image attribute value for thumbnail
- `normalizeWithImage()` -- Returns normalized product data with image URL
- `newEloquentBuilder($query)` -- Returns custom `Database\Eloquent\Builder`

#### Presenters

- `values` => `ProductValuesPresenter`
- `status` => `BooleanPresenter`

### Repository Pattern

#### `ProductRepository` extends `Webkul\Core\Eloquent\Repository`

**Dependencies Injected:**
- `AttributeRepository` -- For attribute operations
- `Container` -- For service resolution

**Public Methods:**

| Method | Description |
|---|---|
| `model()` | Returns `'Webkul\Product\Contracts\Product'` |
| `create(array $data)` | Delegates to product type's `create()` method |
| `update(array $data, $id, $attribute)` | Delegates to product type's `update()` |
| `updateWithValues(array $data, int\|string $id)` | Updates only the values key |
| `updateStatus(bool $status, int $id)` | Updates product status directly |
| `copy($id)` | Deep copies product via type instance (transaction-wrapped) |
| `isUniqueVariantForProduct($productId, $configAttributes, $sku, $variantId)` | Validates variant uniqueness |
| `findBySlug($slug)` | Finds via `url_key` attribute |
| `findBySlugOrFail($slug)` | Throws `ModelNotFoundException` if not found |
| `getAll()` | Alias for `searchFromDatabase()` |
| `searchFromDatabase()` | Paginates products with query builder filtering |
| `queryBuilderFromDatabase($params)` | Builds complex query with left join on variants |
| `getPerPageLimit(array $params)` | Delegates to `product_toolbar` helper |
| `getSortOptions(array $params)` | Delegates to `product_toolbar` helper |
| `getSuperAttributes($product)` | Returns super attributes with options formatted |

**Contract:** `Product` interface (empty marker interface)

### Query Builder Pattern

**Custom Builder:** `Database\Eloquent\Builder`
- Extends Eloquent Builder with product-specific query methods

### Observers & Listeners

**`ProductObserver`:**
- `deleted($product)` -- Deletes product storage directory

**`Product` Listener:**
- `getAllRelatedProductIds($product)` -- Returns product + parent/variant IDs for indexing

### Type System

#### `Product\Type\AbstractType`

Base class for all product types with configurable behavior.

**Constants (Value Keys):**

| Constant | Value |
|---|---|
| `PRODUCT_VALUES_KEY` | `'values'` |
| `COMMON_VALUES_KEY` | `'common'` |
| `LOCALE_VALUES_KEY` | `'locale_specific'` |
| `CHANNEL_VALUES_KEY` | `'channel_specific'` |
| `CHANNEL_LOCALE_VALUES_KEY` | `'channel_locale_specific'` |
| `CATEGORY_VALUES_KEY` | `'categories'` |
| `ASSOCIATION_VALUES_KEY` | `'associations'` |
| Association keys | `related_products`, `up_sells`, `cross_sells` |

**Core Methods:**

- `create(array $data)` -- Creates product with values structure
- `update(array $data, $id, $attribute)` -- Delegates update with value preparation
- `prepareProductValues(array $data, $product)` -- Processes attribute values
- `updateWithValues(array $data, int|string $id)` -- Updates only values without reprocessing
- `getEditableAttributes($group, $skipSuperAttribute)` -- Returns attributes for UI

**Protected Properties:**

| Property | Type | Description |
|---|---|---|
| `$product` | Product | Product instance |
| `$canBeCopied` | bool | Copy capability flag |
| `$hasVariants` | bool | Children support |
| `$isChildrenCalculated` | bool | Children price calculation |
| `$skipAttributes` | array | Attributes to skip |
| `$additionalViews` | array | Blade views to include |

**Dependencies Injected:**
- `AttributeRepository`
- `ProductRepository`
- `FileStorer`

#### Concrete Types

- **`Simple`** -- Basic product type
- **`Configurable`** -- Has variants with super attributes

### Config

**`Config/product_types.php`:**

```php
'simple' => [
    'key'   => 'simple',
    'class' => 'Webkul\Product\Type\Simple',
    'sort'  => 1,
],
'configurable' => [
    'key'   => 'configurable',
    'class' => 'Webkul\Product\Type\Configurable',
    'sort'  => 2,
],
```

### Filters (Advanced Query Pattern)

**Filter hierarchy for Database and ElasticSearch:**

- `FilterManager` -- Orchestrates filters
- `AbstractFilter` -- Base for all filters
- `AbstractAttributeFilter` -- Base for attribute filtering
- `AbstractPropertyFilter` -- Base for property filtering
- `Database/AbstractDatabaseAttributeFilter` -- Database-specific attribute filters
- `ElasticSearch/AbstractElasticSearchAttributeFilter` -- ES-specific attribute filters

**Database Filters:**
- `BooleanFilter`, `DateFilter`, `PriceFilter`, `TextFilter`, `SkuOrUniversalFilter`

**Property Filters:**
- `DateTimeFilter`, `FamilyFilter`, `IdFilter`, `ParentFilter`, `SkuFilter`, `StatusFilter`, `TypeFilter`

---

## Attribute Domain

**Package:** `packages/Webkul/Attribute/src/`

### Model Hierarchy

#### Core Model: `Attribute` extends `TranslatableModel`

- **Implements:** `AttributeContract`, `HistoryAuditable`
- **Traits:** `HasFactory`, `HistoryTrait`
- **Translatable:** `['name']`
- **Fillable:** `code`, `type`, `enable_wysiwyg`, `position`, `swatch_type`, `is_required`, `is_unique`, `validation`, `regex_pattern`, `value_per_locale`, `value_per_channel`, `is_filterable`, `ai_translate`

**Type Constants:**

| Constant | Description |
|---|---|
| `TEXT_TYPE` | Text input |
| `TEXTAREA_TYPE` | Textarea input |
| `BOOLEAN_FIELD_TYPE` | Boolean toggle |
| `PRICE_FIELD_TYPE` | Price field |
| `SELECT_FIELD_TYPE` | Single select |
| `MULTISELECT_FIELD_TYPE` | Multi-select |
| `DATETIME_FIELD_TYPE` | Date and time |
| `DATE_FIELD_TYPE` | Date only |
| `CHECKBOX_FIELD_TYPE` | Checkbox |
| `FILE_ATTRIBUTE_TYPE` | File upload |
| `IMAGE_ATTRIBUTE_TYPE` | Image upload |
| `GALLERY_ATTRIBUTE_TYPE` | Image gallery |
| `NON_DELETABLE_ATTRIBUTE_CODE` | `'sku'` |

**Relations:**
- `options()` -- HasMany `AttributeOption`

**Key Methods:**

- `getValidationsField()` -- Returns formatted validation string for UI
- `getValidationRules($channel, $locale, $id, $withUnique)` -- Returns Laravel validation rules
- `getValidationsOnlyMedia()` -- Media-only validation rules
- `getFilterType()` -- Maps attribute type to filter type
- `getScope($locale, $channel)` -- Returns JSON path scope for value access
- `getValueFromProductValues($values, $channel, $locale)` -- Extracts value from values array
- `getFlatAttributeName($channel, $locale)` -- Returns flat form field name
- `getAttributeInputFieldName($channel, $locale)` -- Returns HTML form field name
- `getJsonPath($channel, $locale)` -- Returns JSON path for database queries
- `canBeDeleted()` -- Checks if attribute can be deleted
- `setProductValue($value, &$productValues, $channel, $locale)` -- Sets value in product values
- `isLocaleBasedAttribute()` -- Boolean check
- `isChannelBasedAttribute()` -- Boolean check
- `isLocaleAndChannelBasedAttribute()` -- Boolean check
- `fieldTypeValidations()` -- Returns type-specific validation rules
- `getOptionsByCodeAndLocale($codes, $locale)` -- Retrieves options with locale-aware label

#### `AttributeFamily` extends `TranslatableModel`

- **Implements:** `AttributeFamilyContract`, `HistoryAuditable`
- **Translatable:** `['name']`
- **Fillable:** `code`
- **HasMany:** `attributeFamilyGroupMappings()` (ordered by position)
- **HasMany:** `products()`

**Methods:**
- `customAttributes()` -- Complex join query returning family's attributes with `group_id`
- `getCustomAttributesAttribute()` -- Magic accessor returning attributes collection
- `getConfigurableAttributes()` -- Returns only select-type attributes for variants
- `familyGroups()` -- BelongsToMany relationship to `AttributeGroup`

#### `AttributeGroup` extends `TranslatableModel`

- **Implements:** `AttributeGroupContract`, `HistoryAuditable`
- **Translatable:** `['name']`
- **Fillable:** `code`, `column`, `position`
- **HasMany:** `groupMappings()`

**Method:**
- `customAttributes($familyId)` -- Returns attributes for specific family

#### `AttributeOption` extends `TranslatableModel`

- **Implements:** `AttributeOptionContract`, `HistoryAuditable`
- **Translatable:** `['label']`
- **Fillable:** `code`, `swatch_value`, `sort_order`, `attribute_id`
- **Appends:** `['swatch_value_url']`
- **BelongsTo:** `attribute()`

**Method:**
- `swatch_value_url()` -- Resolves image swatch to storage URL

#### `AttributeFamilyGroupMapping` Model

- Links families to groups to attributes with position ordering
- Supports many-to-many through `attribute_group_mappings`

### Repository Pattern

#### `AttributeRepository` extends `Repository`

**Dependencies:** `AttributeOptionRepository`

**Methods:**

| Method | Description |
|---|---|
| `model()` | Returns `Attribute::class` |
| `create(array $data)` | Creates attribute with options if select/multiselect/checkbox |
| `update(array $data, $id, $attribute)` | Updates with option handling; protects SKU filterability |
| `validateUserInput($data)` | Removes `is_unique` for non-text types |
| `getProductDefaultAttributes($codes)` | Returns default attributes (name, description, price, status, etc.) |
| `getFamilyAttributes($attributeFamily)` | Cached family attribute lookup |
| `getPartial()` | Returns trimmed attribute list (select/multiselect only) |
| `findVariantOption($attribute, $option)` | Finds variant options with type validation |
| `queryBuilder()` | Eager loads translations |
| `getAttributeListBySearch($search, $columns, $excludeTypes)` | DB table search with locale join |

#### `AttributeFamilyRepository` extends `Repository`

**Dependencies:**
- `AttributeRepository`
- `AttributeGroupRepository`
- `AttributeFamilyGroupMappingRepository`

**Methods:**

| Method | Description |
|---|---|
| `model()` | Returns `'Webkul\Attribute\Contracts\AttributeFamily'` |
| `create(array $data)` | Creates family with nested groups and attributes |
| `update(array $data, $id, $attribute)` | Complex update tracking added/removed attributes |
| `getPartial()` | Returns trimmed family list |
| `queryBuilder()` | Eager loads translations and group mappings with attributes |

**Events dispatched by `update()`:**
- `catalog.attribute_family.attributes.changed` -- When family attributes added/removed
- `core.model.proxy.sync.AttributeFamilyGroupMapping` -- When family structure changes

#### Additional Repositories

- `AttributeGroupRepository` extends `Repository`
- `AttributeOptionRepository` extends `Repository`
- `AttributeFamilyGroupMappingRepository` extends `Repository`
- `AttributeOptionTranslationRepository` extends `Repository`

### Contracts (Marker Interfaces)

All contracts are minimal marker interfaces:
- `Attribute`, `AttributeFamily`, `AttributeGroup`, `AttributeOption`
- `AttributeFamilyGroupMapping`, `AttributeTranslation`, `AttributeGroupTranslation`
- `AttributeFamilyTranslation`, `AttributeOptionTranslation`
- `AttributeNormalizerInterface`

### Validation Rules

| Rule Class | Description |
|---|---|
| `AttributeTypes` | Validates attribute type field |
| `SwatchTypes` | Validates swatch type |
| `ValidSwatchValue` | Validates swatch value format |
| `ValidationTypes` | Validates validation field |
| `NotSupportedAttributes` | Prevents creating unsupported attributes |

### Services

#### `AttributeService`

Core service for attribute operations.

#### Normalizers (Strategy Pattern)

- `AbstractNormalizer` -- Base for all normalizers
- `DefaultNormalizer` -- Default value normalization
- `OptionNormalizer` -- Option-specific normalization
- `PriceNormalizer` -- Price field normalization

#### `AttributeNormalizerFactory`

Factory for creating appropriate normalizer by attribute type.

### Config

**`Config/attribute_types.php`:**
- Defines all 12 attribute types with keys and localization keys

---

## Category Domain

**Package:** `packages/Webkul/Category/src/`

### Model Hierarchy

#### Core Model: `Category` extends `Model`

- **Implements:** `CategoryContract`, `HistoryAuditable`, `PresentableHistoryInterface`
- **Traits:** `HasFactory`, `NodeTrait` (Nested Set), `Visitable`, `HistoryTrait`
- **Fillable:** `code`, `parent_id`
- **Casts:** `additional_data` (array)
- **Appends:** `['name']`

**Relations:**
- `parent_category()` -- BelongsTo (self-referencing)
- `NodeTrait` provides hierarchical methods: `children`, `descendants`, `ancestors`, etc.

**Methods:**
- `name()` -- Attribute accessor extracting from `additional_data[locale_specific][locale]['name']`
- `getPresenters()` -- Returns presenter mapping
- `newEloquentBuilder($query)` -- Returns custom `Database\Eloquent\Builder`

#### `CategoryField` Model

- **Fillable:** Field configuration
- **Relations:** Options, translations

#### `CategoryFieldOption` Model

- **Translatable:** `['label']`

### Repository Pattern

#### `CategoryRepository` extends `Repository`

**Constants:**

| Constant | Value |
|---|---|
| `ADDITIONAL_VALUES_KEY` | `'additional_data'` |
| `LOCALE_VALUES_KEY` | `'locale_specific'` |
| `COMMON_VALUES_KEY` | `'common'` |

**Injected Dependencies:** `FileStorer`

**Methods:**

| Method | Description |
|---|---|
| `model()` | Returns `Category::class` |
| `getAll(array $params)` | Filters by `parent_id` or `only_children`, paginates |
| `create(array $data, $withoutFormattingValues)` | Creates with `additional_data` preparation |
| `update(array $data, $id, $attribute, $withoutFormattingValues)` | Updates with data preparation |
| `getCategoryTree($id)` | Returns full tree (nested set `toTree()`) |
| `getCategoryTreeWithoutDescendant($id)` | Returns tree excluding ID and descendants |
| `getTreeBranchToParent($category, $present)` | Returns ancestor path |
| `getRootCategories()` | Returns top-level categories |
| `getChildCategories($parentId, $categoryId)` | Returns siblings excluding ID |
| `getVisibleCategoryTree($id)` | Returns visible subtree |
| `getPartial($columns)` | Returns trimmed list (id, name, slug) |
| `getProducts($code)` | Returns products containing category code in `values.categories` |
| `queryBuilder()` | Eager loads `parent_category` |

**Data Processing Methods:**
- `prepareAdditionalData($data, $category)` -- Processes file uploads, merges with existing data
- `processAdditionalDataValues($categoryId, $values, $categoryValues)` -- Handles `UploadedFile` instances

### Observers

**`CategoryObserver`:**
- `deleted($category)` -- Deletes storage directory
- `saved($category)` -- Touches all children (updates timestamps)

### Validators

| Validator Class | Description |
|---|---|
| `CategoryRequestValidator` | Request validation |
| `CategoryValidator` | Model validation |
| `CategoryMediaValidator` | Media-specific validation |
| `FieldValidator` | Field validation |

### Config

**`Config/category_field_types.php`:**
- Defines category field types similar to attributes

---

## User/Auth Domain

**Package:** `packages/Webkul/User/src/`

### Model Hierarchy

#### `Admin` extends `Authenticatable`

- **Implements:** `AdminContract`, `AuditableContract`
- **Traits:** `Auditable`, `HasApiTokens`, `HasFactory`, `Notifiable`
- **Fillable:** `name`, `email`, `password`, `image`, `api_token`, `role_id`, `ui_locale_id`, `status`, `timezone`
- **Hidden:** `password`, `api_token`, `remember_token`
- **Auditable:** Tracks changes via Laravel Auditing package

**Relations:**

| Relation | Type | Target |
|---|---|---|
| `role()` | BelongsTo | `Role` |
| `apiKey()` | HasOne | `Apikey` |
| `uiLocale()` | BelongsTo | `Locale` |
| `notifications()` | HasMany | `UserNotification` |

**Methods:**
- `image_url()` -- Returns Storage URL for image
- `getImageUrlAttribute()` -- Accessor
- `toArray()` -- Includes `image_url` in output
- `hasPermission($permission)` -- Checks role permissions array
- `sendPasswordResetNotification($token)` -- Sends reset email
- `findForPassport($username)` -- Finds by email for OAuth

#### `Role` extends `Model`

- **Implements:** `RoleContract`, `HistoryAuditable`
- **Traits:** `HasFactory`, `HistoryTrait`
- **Fillable:** `name`, `description`, `permission_type`, `permissions`
- **Casts:** `permissions` (array)
- **HasMany:** `admins()`

**History Configuration:**
- Tags: `['role']`
- Fields: `['name', 'description']`

### Repository Pattern

#### `AdminRepository` extends `Repository`

**Methods:**
- `model()` -- Returns `'Webkul\User\Contracts\Admin'`
- `countAdminsWithAllAccess()` -- Counts admins with `permission_type='all'`
- `countAdminsWithAllAccessAndActiveStatus()` -- Counts active admins with `permission_type='all'`

#### `RoleRepository` extends `Repository`

### Bouncer (Authorization)

**`Bouncer` class:**
- Authorization system for permission checking
- Facade: `Webkul\User\Facades\Bouncer`

**Middleware:**
- `Http\Middleware\Bouncer` -- Authorization middleware

### Contracts

Minimal marker interfaces:
- `Admin`
- `Role`

---

## DataTransfer Domain

**Package:** `packages/Webkul/DataTransfer/src/`

### Model Hierarchy

#### `JobInstances` extends `Model`

- **Implements:** `JobInstancesContract`, `HistoryAuditable`
- **Traits:** `HasFactory`, `HistoryTrait`
- **Table:** `job_instances`
- **Fillable:** `code`, `entity_type`, `type`, `action`, `validation_strategy`, `allowed_errors`, `field_separator`, `file_path`, `images_directory_path`, `filters`
- **Casts:** `filters` (array)
- **HasMany:** `batches()` -> `JobTrack`

**History Tags:** `['job_instance']`

#### `JobTrack` Model

- Tracks individual import/export job progress
- Relationships to `JobInstances` and `JobTrackBatch`

#### `JobTrackBatch` Model

- Groups multiple job track records

### Repository Pattern

#### `JobInstancesRepository` extends `Repository`

**Methods:**

| Method | Description |
|---|---|
| `model()` | Returns `JobInstances::class` |
| `normalizeData(array $data)` | DB-driver-specific normalization (PostgreSQL converts empty strings to null) |
| `create(array $data)` | Normalizes then creates |
| `update(array $data, $id)` | Normalizes then updates |

#### Additional Repositories

- `JobTrackRepository`
- `JobTrackBatchRepository`

### Jobs (Queue)

#### Export Jobs

| Job Class | Description |
|---|---|
| `Export/ExportBatch` | Processes export batch |
| `Export/ExportTrackBatch` | Tracks export progress |
| `Export/Completed` | Finalizes export |
| `Export/UploadFile` | Uploads to storage |
| `Export/File/FlatItemBuffer` | Flat file buffer |
| `Export/File/JSONFileBuffer` | JSON buffer |
| `Export/File/SpoutWriterFactory` | Writer factory for CSV/Excel |
| `Export/File/TemporaryFileFactory` | Creates temp files (local/remote) |

#### Import Jobs

| Job Class | Description |
|---|---|
| `Import/ImportBatch` | Processes import batch |
| `Import/ImportTrackBatch` | Tracks import progress |
| `Import/Completed` | Finalizes import |
| `Import/Indexing` | ElasticSearch indexing post-import |
| `Import/Linking` | Creates product associations post-import |
| `Import/JobTrackBatch` | Creates track records |

#### System Jobs

- `System/BulkProductUpdate` -- Bulk updates via API

### Helpers / Services

#### Import/Export Helpers

- `AbstractJob` -- Base job class
- `Import` -- Import orchestration
- `Export` -- Export orchestration
- `Error` -- Error handling

#### Importers (Strategy Pattern)

- `AbstractImporter` -- Base importer
- `Product/Importer` -- Product import with 40+ error codes
- `Category/Importer` -- Category import

**Product Importer Capabilities:**
- Handles simple, virtual, downloadable, configurable, grouped types
- Validates attribute family, attributes, SKU uniqueness
- Creates variant associations
- Processes media (images in specified directory)
- Error codes: `invalid_type`, `sku_not_found_to_delete`, `duplicated_url_key`, `attribute_family_code_not_found`, etc.

#### Exporters (Strategy Pattern)

- `AbstractExporter` -- Base exporter
- `Product/Exporter` -- Product export
- `Product/SKUStorage` -- SKU deduplication

#### Sources

- `AbstractSource` -- Base source
- `CSV` -- CSV parser
- `Excel` -- Excel parser
- `ProductCursor` -- Database cursor for products
- `Elastic/ProductCursor` -- ElasticSearch cursor

#### Formatters

- `EscapeFormulaOperators` -- Escapes CSV formula injection

#### Field Processor

- `FieldProcessor` -- Processes field values during import

### Contracts

- `JobInstances` -- Model contract
- `JobTrack` -- Track contract
- `JobTrackBatch` -- Batch contract
- `Validator/JobValidator` -- Validation contract

---

## MagicAI Domain

**Package:** `packages/Webkul/MagicAI/src/`

### Core Class: `MagicAI` Orchestrator

Builder pattern for LLM configuration. Supports: OpenAI, Groq, Gemini, Ollama.

#### Configuration Methods

| Method | Description |
|---|---|
| `setPlatForm($platform)` | Sets LLM platform |
| `setModel($model)` | Sets model identifier |
| `setAgent($agent)` | Sets agent type |
| `setPrompt($prompt, $fieldType)` | Sets user prompt (adds HTML/text suffix) |
| `setSystemPrompt($systemPrompt)` | Sets system prompt |
| `setStream(bool)` | Enables streaming |
| `setRaw(bool)` | Sets raw response mode |
| `setTemperature(float)` | Sets temperature (0-1) |
| `setMaxTokens(int)` | Sets token limit |

#### Execution Methods

| Method | Description |
|---|---|
| `ask()` | Executes prompt, returns text response |
| `images(array $options)` | Generates images (OpenAI only) |
| `getModelInstance()` | Returns concrete `LLMModelInterface` implementation |
| `getModelList()` | Returns available models |

#### Platform Constants

| Constant | Description |
|---|---|
| `MAGIC_OPEN_AI` | OpenAI platform |
| `MAGIC_GROQ_AI` | Groq platform |
| `MAGIC_OLLAMA_AI` | Ollama platform |
| `MAGIC_GEMINI_AI` | Gemini platform |
| `SUFFIX_HTML_PROMPT` | HTML formatting instruction |
| `SUFFIX_TEXT_PROMPT` | Text formatting instruction |

### LLM Service Implementations

All implement `LLMModelInterface`.

#### `OpenAI` Service

- Uses `openai/laravel` package
- **Methods:**
  - `ask()` -- Chat completion with system+user messages
  - `images(array $options)` -- DALL-E image generation (supports `b64_json` response format)
  - `setConfig()` -- Loads API key from config
- Supports models: gpt-4, gpt-3.5-turbo, etc.

#### `Groq`, `Gemini`, `Ollama` Services

- Similar interface, provider-specific implementations

### Models

#### `MagicPrompt` Model

- Stores saved prompts
- Relations to AI system prompts
- Tracks tone and prompt variations

#### `MagicAISystemPrompt` Model

- Stores system prompts by category/domain
- Used as context for AI interactions

### Repositories

- `MagicPromptRepository` -- Prompt CRUD
- `MagicAISystemPromptRepository` -- System prompt CRUD

### Jobs

| Job Class | Description |
|---|---|
| `SaveTranslatedDataJob` | Async job for saving AI-translated content; supports batch translation |
| `SaveTranslatedAllAttributesJob` | Bulk translates all product attributes |

### Contracts

- `LLMModelInterface` -- Defines `ask()` and `images()` contract
- `MagicPrompt` -- Marker interface
- `MagicAISystemPrompt` -- Marker interface

### Config

**`Config/default_prompts.php`:**
- Predefined prompts for common use cases
- Domain-specific: Product descriptions, categories, etc.

### Validator

**`MagicAICredentialValidator`:**
- Validates API credentials before making requests

---

## Cross-Domain Patterns

### Event Dispatching

**Product Domain Events:**
- `catalog.attribute_family.attributes.changed` -- When family attributes added/removed
- `core.model.proxy.sync.AttributeFamilyGroupMapping` -- When family structure changes

**Import/Export Events:**
- Job completion events
- Batch processing events

### History Control Integration

All core models implement `HistoryAuditable` and use `HistoryTrait`:
- Automatic change tracking via `HistoryControl` package
- Configurable audit exclusions and field mappings
- Presenters for history display

**Configuration by Model:**

| Model | Tags | Excluded Fields | Notes |
|---|---|---|---|
| Product | `['product']` | `['id']` | Full values tracking |
| Attribute | `['attribute']` | `['id']` | |
| Category | `['category']` | `['_lft', '_rgt', 'id']` | Nested set fields excluded |
| AttributeFamily | `['attributeFamily']` | -- | Proxy fields tracked |
| Role | `['role']` | -- | Specific fields: name, description |

### Translatable Models

Uses `Webkul\Core\Eloquent\TranslatableModel`:

- `Attribute`, `AttributeFamily`, `AttributeGroup`, `AttributeOption`
- `Category` (implicit via `additional_data`)
- Supports automatic locale-specific relationships

### Proxy Pattern

Used throughout for dependency injection and configuration:

- `AttributeFamilyProxy`, `AttributeProxy`, `AttributeOptionProxy`
- `ProductProxy`, `ProductImageProxy`
- `AdminProxy`, `RoleProxy`
- `LocaleProxy`

### Value Storage Structure

Products store values in JSON with hierarchical scope:

```json
{
  "common": {
    "sku": "SKU123",
    "attr_code": "value"
  },
  "locale_specific": {
    "en_US": {
      "name": "Product Name"
    }
  },
  "channel_specific": {
    "default": {
      "price": "100"
    }
  },
  "channel_locale_specific": {
    "default": {
      "en_US": {
        "description": "..."
      }
    }
  },
  "categories": ["cat_code1", "cat_code2"],
  "associations": {
    "related_products": [1, 2, 3],
    "up_sells": [4, 5],
    "cross_sells": [6, 7]
  }
}
```

### File Storage

- **Products:** `product/{product_id}/{field_name}/{hash}`
- **Categories:** `category/{category_id}/{field_name}/{hash}`
- Observer-based cleanup on delete

### Validation Rules

Custom rules throughout domains:

- Slug validation for SKU/URLs
- Attribute type-specific validation
- Unique constraints (database and ElasticSearch)
- File/image validation with size limits
- Option validation for select/multiselect

---

## Architecture Summary

The UnoPim domain layer follows these architectural principles:

| # | Principle | Description |
|---|---|---|
| 1 | **Repository Pattern** | All data access through repositories, not direct queries |
| 2 | **Contract/Interface Segregation** | Minimal marker interfaces in Contracts directory |
| 3 | **Type Polymorphism** | Product types as pluggable strategies |
| 4 | **Service Layer** | Complex operations in dedicated services (AttributeService, MagicAI) |
| 5 | **Observer Pattern** | Model lifecycle hooks for side effects (storage cleanup, tree updates) |
| 6 | **Event Dispatching** | Domain events for cross-domain communication |
| 7 | **Factory Pattern** | Normalizers, builders, and type instantiation factories |
| 8 | **Strategy Pattern** | Importers, exporters, filters as interchangeable strategies |
| 9 | **Nested Set Trees** | Categories use Kalnoy/nestedset for hierarchy |
| 10 | **JSON Value Storage** | Multi-channel/locale data in JSON columns with path access |
| 11 | **Translatable Models** | Built-in i18n support through TranslatableModel |
| 12 | **History Auditing** | Automatic change tracking via HistoryControl package |
| 13 | **Proxy Pattern** | Configuration-based model resolution for extensibility |
