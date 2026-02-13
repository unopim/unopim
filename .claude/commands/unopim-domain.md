# UnoPim DOMAIN Layer Skill

Use this skill when working with Products, Attributes, Categories, Users/Roles, DataTransfer (import/export), or MagicAI domain logic.

## Product Domain

**Package:** `packages/Webkul/Product/src/`

### Type System (Strategy Pattern)

```php
// Config: packages/Webkul/Product/src/Config/product_types.php
'simple'       => ['key' => 'simple', 'class' => 'Webkul\Product\Type\Simple', 'sort' => 1],
'configurable' => ['key' => 'configurable', 'class' => 'Webkul\Product\Type\Configurable', 'sort' => 2],
```

Get type instance: `$product->getTypeInstance()` returns `Simple` or `Configurable`

**AbstractType** core methods:
- `create(array $data)` - Creates product with values structure
- `update(array $data, $id)` - Updates with value preparation
- `prepareProductValues(array $data, $product)` - Processes attribute values into JSON
- `getEditableAttributes($group, $skipSuperAttribute)` - Returns attributes for UI

### ProductRepository

Delegates to type instance:
```php
$repo->create($data);              // Calls $type->create()
$repo->update($data, $id);         // Calls $type->update()
$repo->updateWithValues($data, $id); // Updates only values key
$repo->copy($id);                   // Deep copy via type instance (transaction-wrapped)
```

### Product Observer
- `deleted($product)` - Deletes product storage directory

## Attribute Domain

**Package:** `packages/Webkul/Attribute/src/`

### 12 Attribute Types

```php
// Constants on Webkul\Attribute\Models\Attribute
TEXT_TYPE, TEXTAREA_TYPE, BOOLEAN_FIELD_TYPE, PRICE_FIELD_TYPE,
SELECT_FIELD_TYPE, MULTISELECT_FIELD_TYPE, DATETIME_FIELD_TYPE,
DATE_FIELD_TYPE, CHECKBOX_FIELD_TYPE, FILE_ATTRIBUTE_TYPE,
IMAGE_ATTRIBUTE_TYPE, GALLERY_ATTRIBUTE_TYPE
```

### Hierarchy
```
AttributeFamily (code, translatable name)
  └─ AttributeFamilyGroupMapping (position)
       └─ AttributeGroup (code, column, position, translatable name)
            └─ attribute_group_mappings (pivot: attribute_id, position)
                 └─ Attribute (code, type, value_per_locale, value_per_channel)
                      └─ AttributeOption (code, swatch_value, translatable label)
```

### Key Attribute Methods
```php
$attr->getValueFromProductValues($values, $channel, $locale); // Read from values JSON
$attr->setProductValue($value, $productValues, $channel, $locale); // Write to values JSON
$attr->getJsonPath($channel, $locale);  // JSON path for DB queries
$attr->getValidationRules($channel, $locale, $id, $withUnique); // Laravel rules
$attr->isLocaleBasedAttribute();    // bool
$attr->isChannelBasedAttribute();   // bool
```

### Normalizers (Strategy Pattern)
- `AttributeNormalizerFactory` creates: `DefaultNormalizer`, `OptionNormalizer`, `PriceNormalizer`

## Category Domain

**Package:** `packages/Webkul/Category/src/`

### Nested Set (kalnoy/nestedset)
```php
class Category extends Model {
    use NodeTrait;  // Provides _lft, _rgt, children, descendants, ancestors
    protected $fillable = ['code', 'parent_id'];
    protected $casts = ['additional_data' => 'array'];
    protected $appends = ['name']; // Computed from additional_data
}
```

**Name accessor:** `$this->additional_data['locale_specific'][core()->getRequestedLocaleCode()]['name']`

### CategoryRepository Constants
```php
ADDITIONAL_VALUES_KEY = 'additional_data'
LOCALE_VALUES_KEY     = 'locale_specific'
COMMON_VALUES_KEY     = 'common'
```

Key methods: `getCategoryTree($id)`, `getCategoryTreeWithoutDescendant($id)`, `getRootCategories()`

### CategoryObserver
- `deleted($category)` - Deletes storage directory
- `saved($category)` - Touches all children

## User & RBAC Domain

**Package:** `packages/Webkul/User/src/`

```php
// Admin extends Authenticatable (not Model)
// Traits: Auditable, HasApiTokens, HasFactory, Notifiable
$admin->hasPermission('catalog.products.create'); // Check permission
$admin->role->permission_type; // 'all' or 'custom'
$admin->role->permissions;     // Array of ACL keys
```

**Bouncer helper:** `bouncer()->hasPermission('catalog.products.edit')` - returns bool
**In views:** `@if (bouncer()->hasPermission('admin.settings.roles.index'))`

## DataTransfer Domain

**Package:** `packages/Webkul/DataTransfer/src/`

### Job Flow
```
JobInstances (config) → JobTrack (execution) → JobTrackBatch (batches)
```

**States:** pending → validated → processing → completed/failed

### Import/Export Strategy
- `Importer` / `Exporter` base classes with entity-specific implementations
- Jobs in `packages/Webkul/DataTransfer/src/Jobs/` (Import/Export subdirs)
- `SpoutWriterFactory` for CSV/Excel output
- Events: `data_transfer.imports.completed`, `data_transfer.export.completed`

## MagicAI Domain

**Package:** `packages/Webkul/MagicAI/src/`

```php
// Builder pattern
$magicAI = new MagicAI();
$result = $magicAI->setPrompt($prompt)->setModel($model)->generate();
```

**Interface:** `LLMModelInterface` with implementations:
- `OpenAIService`, `GroqService`, `GeminiService`, `OllamaService`

## Cross-Domain Patterns

**Event dispatching between domains:**
```php
Event::dispatch('catalog.product.create.after', $product);
Event::dispatch('catalog.attribute_family.attributes.changed', $family);
Event::dispatch('core.model.proxy.sync.AttributeFamilyGroupMapping', $mapping);
```

**Proxy pattern:** Concord proxies enable loose coupling - models referenced via Contracts, resolved at runtime.

## Key Rules

- Product values are JSON-stored, NOT EAV - never add value columns to products table
- Categories use Nested Set - never manipulate `_lft`/`_rgt` directly, use NodeTrait methods
- SKU attribute (code='sku') is protected - `NON_DELETABLE_ATTRIBUTE_CODE = 'sku'`
- ProductRepository ALWAYS delegates create/update to the type instance
- All domain contracts are minimal marker interfaces - implement them on models
- Use `FileStorer` for file uploads, not direct filesystem calls
- DataTransfer normalizes data per DB driver (PostgreSQL converts empty strings to null)
