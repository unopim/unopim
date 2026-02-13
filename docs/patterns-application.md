# UnoPim - APPLICATION Layer Patterns & Skills

> Reference documentation for the Application architectural layer.
> Generated from exhaustive codebase scan - 2026-02-08

---

## Table of Contents

1. [Base Controller Pattern](#1-base-controller-pattern)
2. [Admin Controllers - Catalog](#2-admin-controllers---catalog)
3. [Admin Controllers - Settings](#3-admin-controllers---settings)
4. [Request Validation Classes](#4-request-validation-classes)
5. [API Controllers](#5-api-controllers)
6. [API Routes](#6-api-routes)
7. [Admin Routes Structure](#7-admin-routes-structure)
8. [HTTP Resources (API Response Transformers)](#8-http-resources-api-response-transformers)
9. [ACL (Access Control List) Configuration](#9-acl-access-control-list-configuration)
10. [Menu Configuration](#10-menu-configuration)
11. [DataGrid Pattern](#11-datagrid-pattern)
12. [Event System](#12-event-system)
13. [Middleware Pattern](#13-middleware-pattern)
14. [Request/Response Flow](#14-requestresponse-flow)
15. [Key Architectural Patterns Summary](#15-key-architectural-patterns-summary)

---

## 1. Base Controller Pattern

### Admin Base Controller

**Location:** `packages/Webkul/Admin/src/Http/Controllers/Controller.php`

```php
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function redirectToLogin()
    {
        return redirect()->route('admin.session.create');
    }
}
```

**Pattern:**
- Extends Laravel's `BaseController`
- Uses three core traits: `AuthorizesRequests`, `DispatchesJobs`, `ValidatesRequests`
- All admin controllers inherit from this base
- Provides redirect utility for authentication

### API Base Controller

**Location:** `packages/Webkul/AdminApi/src/Http/Controllers/API/ApiController.php`

```php
class ApiController extends BaseController
{
    use ApiResponse, DispatchesJobs, HtmlPurifier, ValidatesRequests;

    protected function setLabels(array $requestData, string $labelKey = 'name')
    protected function codeRequireWithUniqueValidator(string $table, array $newRules = [])
    protected function validator(array $Rules = [])
}
```

**Pattern:**
- Specialized for API responses
- Includes `ApiResponse` trait for JSON formatting
- Includes `HtmlPurifier` trait for security
- Helper methods for validation and label transformation

---

## 2. Admin Controllers - Catalog

### ProductController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Catalog/ProductController.php`

**Class Hierarchy:**
```
Controller (base)
  +-- ProductController
```

**Constructor Injection:**
```php
public function __construct(
    protected AttributeFamilyRepository $attributeFamilyRepository,
    protected ProductRepository $productRepository,
    protected ProductValuesValidator $valuesValidator,
    protected ChannelRepository $channelRepository,
    protected AttributeRepository $attributeRepository,
)
```

**Methods and Routes:**

| Method | Route | HTTP | Name | Returns |
|--------|-------|------|------|---------|
| `index()` | `/admin/catalog/products` | GET | `admin.catalog.products.index` | View or DataGrid JSON |
| `store()` | `/admin/catalog/products` | POST | `admin.catalog.products.store` | JsonResponse |
| `edit(int $id)` | `/admin/catalog/products/edit/{id}` | GET | `admin.catalog.products.edit` | View |
| `update(ProductForm $request, int $id)` | `/admin/catalog/products/edit/{id}` | PUT | `admin.catalog.products.update` | Redirect |
| `copy(int $id)` | `/admin/catalog/products/copy/{id}` | POST | `admin.catalog.products.copy` | JsonResponse |
| `destroy(int $id)` | `/admin/catalog/products/edit/{id}` | DELETE | `admin.catalog.products.delete` | JsonResponse |
| `massDestroy()` | `/admin/catalog/products/mass-delete` | POST | `admin.catalog.products.mass_delete` | JsonResponse |
| `massUpdate()` | `/admin/catalog/products/mass-update` | POST | `admin.catalog.products.mass_update` | JsonResponse |
| `sync()` | `/admin/catalog/sync` | GET | - | Redirect |
| `search()` | `/admin/catalog/products/search` | GET | `admin.catalog.products.search` | JsonResponse |
| `checkVariantUniqueness()` | `/admin/catalog/products/check-variant` | POST | `admin.catalog.products.check-variant` | JsonResponse |
| `getLocale()` | `/admin/catalog/products/get/locale` | GET | `admin.catalog.product.get_locale` | JsonResponse |
| `getAttribute()` | `/admin/catalog/products/get/attributes` | GET | `admin.catalog.product.get_attribute` | JsonResponse |

**Event Dispatches:**
```php
Event::dispatch('catalog.product.create.before');
Event::dispatch('catalog.product.create.after', $product);
Event::dispatch('catalog.product.update.before', $id);
Event::dispatch('catalog.product.update.after', $product);
Event::dispatch('catalog.product.delete.before', $id);
Event::dispatch('catalog.product.delete.after', $id);
Event::dispatch('products.datagrid.sync', true);
```

**Validation Example (store):**
```php
$this->validate(request(), [
    'type'                => 'required',
    'attribute_family_id' => 'required',
    'sku'                 => ['required', 'unique:products,sku', new Slug],
    'super_attributes'    => 'array|min:1',
]);
```

---

### CategoryController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php`

**Constructor:**
```php
public function __construct(
    protected ChannelRepository $channelRepository,
    protected CategoryRepository $categoryRepository,
    protected CategoryFieldRepository $categoryFieldRepository
) {
    $this->categoryValidator = new CategoryRequestValidator(...);
}
```

**Methods and Routes:**

| Method | Route | HTTP | Name |
|--------|-------|------|------|
| `index()` | `/admin/catalog/categories` | GET | `admin.catalog.categories.index` |
| `create()` | `/admin/catalog/categories/create` | GET | `admin.catalog.categories.create` |
| `store(CategoryRequest)` | `/admin/catalog/categories/create` | POST | `admin.catalog.categories.store` |
| `edit(int $id)` | `/admin/catalog/categories/edit/{id}` | GET | `admin.catalog.categories.edit` |
| `update(CategoryRequest, int $id)` | `/admin/catalog/categories/edit/{id}` | PUT | `admin.catalog.categories.update` |
| `destroy(int $id)` | `/admin/catalog/categories/edit/{id}` | DELETE | `admin.catalog.categories.delete` |
| `massDestroy()` | `/admin/catalog/categories/mass-delete` | POST | `admin.catalog.categories.mass_delete` |
| `tree(Request)` | `/admin/catalog/categories/tree` | POST | `admin.catalog.categories.tree` |
| `children()` | `/admin/catalog/categories/children-tree` | GET | `admin.catalog.categories.children.tree` |
| `search()` | `/admin/catalog/categories/search` | GET | `admin.catalog.categories.search` |

**Key Pattern:**
- Uses dedicated `CategoryRequestValidator` in constructor
- Validates parent category before updates
- Checks if category is related to channels (prevents deletion of root categories)
- Transforms category tree recursively

---

### AttributeController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Catalog/AttributeController.php`

**Constants:**
```php
const AI_TRANSLATE_ENABLED = '1';
const AI_TRANSLATE_DISABLED = '0';
const VALUE_PER_LOCALE_ENABLED = 1;
const TEXT = 'text';
const TEXTAREA = 'textarea';
```

**Methods and Routes:**

| Method | Route | HTTP | Name |
|--------|-------|------|------|
| `index()` | `/admin/catalog/attributes` | GET | `admin.catalog.attributes.index` |
| `create()` | `/admin/catalog/attributes/create` | GET | `admin.catalog.attributes.create` |
| `store()` | `/admin/catalog/attributes/create` | POST | `admin.catalog.attributes.store` |
| `edit(int $id)` | `/admin/catalog/attributes/edit/{id}` | GET | `admin.catalog.attributes.edit` |
| `update(int $id)` | `/admin/catalog/attributes/edit/{id}` | PUT | `admin.catalog.attributes.update` |
| `destroy(int $id)` | `/admin/catalog/attributes/edit/{id}` | DELETE | `admin.catalog.attributes.delete` |
| `massDestroy()` | `/admin/catalog/attributes/mass-delete` | POST | `admin.catalog.attributes.mass_delete` |
| `productSuperAttributes(int $id)` | `/admin/catalog/attributes/product-super` | GET | - |

**Validation (store):**
```php
$this->validate(request(), [
    'code'        => ['required', 'not_in:type,attribute_family_id', 'unique:attributes,code', new Code, new NotSupportedAttributes],
    'type'        => 'required',
    'swatch_type' => [
        'required_if:type,select,multiselect',
        'prohibited_unless:type,select,multiselect',
        new SwatchTypes,
    ],
]);
```

---

### AttributeFamilyController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Catalog/AttributeFamilyController.php`

**Methods and Routes:**

| Method | Route | HTTP | Name |
|--------|-------|------|------|
| `index()` | `/admin/catalog/families` | GET | `admin.catalog.families.index` |
| `create()` | `/admin/catalog/families/create` | GET | `admin.catalog.families.create` |
| `store()` | `/admin/catalog/families/create` | POST | `admin.catalog.families.store` |
| `edit(int $id)` | `/admin/catalog/families/edit/{id}` | GET | `admin.catalog.families.edit` |
| `copy(int $id)` | `/admin/catalog/families/copy/{id}` | GET | `admin.catalog.families.copy` |
| `update(int $id)` | `/admin/catalog/families/edit/{id}` | PUT | `admin.catalog.families.update` |
| `destroy(int $id)` | `/admin/catalog/families/edit/{id}` | DELETE | `admin.catalog.families.delete` |

**Key Pattern - Normalize Method:**
```php
// Normalizes family data structure
private function normalize($attributeFamily = null)
{
    $familyGroupMappings = $attributeFamily?->attributeFamilyGroupMappings()
        ->with('attributeGroups')->get()
        ->map(function ($familyGroupMapping) { ... })
        ->toArray();

    return [
        'locales'         => $this->localeRepository->getActiveLocales(),
        'attributeFamily' => [
            'family'              => $attributeFamily,
            'familyGroupMappings' => $familyGroupMappings ?? [],
        ],
    ];
}
```

---

### Other Catalog Controllers

| Controller | File | Purpose |
|-----------|------|---------|
| `AttributeGroupController` | `Catalog/AttributeGroupController.php` | CRUD for attribute groups |
| `AttributeOptionController` | `Catalog/AttributeOptionController.php` | Manage attribute options/swatches |
| `CategoryFieldController` | `Catalog/CategoryFieldController.php` | CRUD for category custom fields |
| `ProductBulkEditController` | `Catalog/ProductBulkEditController.php` | Bulk edit product values |
| `AjaxOptionsController` | `Catalog/Options/AjaxOptionsController.php` | AJAX-based option lookups |

---

## 3. Admin Controllers - Settings

### ChannelController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Settings/ChannelController.php`

**Methods and Routes:**

| Method | Route | HTTP | Name |
|--------|-------|------|------|
| `index()` | `/admin/settings/channels` | GET | `admin.settings.channels.index` |
| `create()` | `/admin/settings/channels/create` | GET | `admin.settings.channels.create` |
| `store()` | `/admin/settings/channels/create` | POST | `admin.settings.channels.store` |
| `edit(int $id)` | `/admin/settings/channels/edit/{id}` | GET | `admin.settings.channels.edit` |
| `update(int $id)` | `/admin/settings/channels/edit/{id}` | PUT | `admin.settings.channels.update` |
| `destroy(int $id)` | `/admin/settings/channels/edit/{id}` | DELETE | `admin.settings.channels.delete` |

**Validation (store):**
```php
$rules = [
    'code'              => ['required', 'unique:channels,code', new Code],
    'root_category_id'  => 'required',
    'locales'           => ['required', new ConvertToArrayIfNeeded],
    'currencies'        => ['required', new ConvertToArrayIfNeeded],
];

foreach ($locales as $locale) {
    $rules[$locale->code.'.name'] = 'nullable';
}
```

**Events:**
```php
Event::dispatch('core.channel.create.before');
Event::dispatch('core.channel.create.after', $channel);
```

---

### RoleController

**File:** `packages/Webkul/Admin/src/Http/Controllers/Settings/RoleController.php`

**Methods:**

| Method | Route | HTTP | Name |
|--------|-------|------|------|
| `index()` | `/admin/settings/roles` | GET | `admin.settings.roles.index` |
| `create()` | `/admin/settings/roles/create` | GET | `admin.settings.roles.create` |
| `store()` | `/admin/settings/roles/create` | POST | `admin.settings.roles.store` |
| `edit(int $id)` | `/admin/settings/roles/edit/{id}` | GET | `admin.settings.roles.edit` |
| `update(int $id)` | `/admin/settings/roles/edit/{id}` | PUT | `admin.settings.roles.update` |
| `delete(int $id)` | `/admin/settings/roles/edit/{id}` | DELETE | `admin.settings.roles.delete` |

**Validation (store):**
```php
$this->validate(request(), [
    'name'            => 'required',
    'permission_type' => 'required',
    'description'     => 'required',
]);
```

---

### Other Settings Controllers

| Controller | File | Purpose |
|-----------|------|---------|
| `CurrencyController` | `Settings/CurrencyController.php` | CRUD for currencies |
| `LocaleController` | `Settings/LocaleController.php` | CRUD for locales |
| `UserController` | `Settings/UserController.php` | CRUD for admin users |
| `ImportController` | `Settings/DataTransfer/ImportController.php` | Data import workflows |
| `ExportController` | `Settings/DataTransfer/ExportController.php` | Data export workflows |
| `TrackerController` | `Settings/DataTransfer/TrackerController.php` | Job tracking for imports/exports |
| `AbstractJobInstanceController` | `Settings/DataTransfer/AbstractJobInstanceController.php` | Base class for import/export |

---

### Other Admin Controllers

| Controller | File | Purpose |
|-----------|------|---------|
| `DashboardController` | `DashboardController.php` | Dashboard view |
| `DataGridController` | `DataGridController.php` | AJAX datagrid handler |
| `ConfigurationController` | `ConfigurationController.php` | System configuration |
| `ManageColumnController` | `ManageColumnController.php` | DataGrid column management |
| `NotificationController` | `NotificationController.php` | User notifications |
| `TinyMCEController` | `TinyMCEController.php` | TinyMCE editor uploads |
| `MagicAIController` | `MagicAI/MagicAIController.php` | AI-powered features |
| `MagicAISystemPromptController` | `MagicAI/MagicAISystemPromptController.php` | AI prompt management |
| `AccountController` | `User/AccountController.php` | User account/profile |
| `SessionController` | `User/SessionController.php` | Login/logout |
| `ForgetPasswordController` | `User/ForgetPasswordController.php` | Password reset request |
| `ResetPasswordController` | `User/ResetPasswordController.php` | Password reset execution |
| `SelectOptionsController` | `VueJsSelect/SelectOptionsController.php` | Vue.js select component data |
| `AbstractOptionsController` | `VueJsSelect/AbstractOptionsController.php` | Base for select options |

---

## 4. Request Validation Classes

### ProductForm

**File:** `packages/Webkul/Admin/src/Http/Requests/ProductForm.php`

```php
class ProductForm extends FormRequest
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $product = $this->productRepository->find($this->id);
        $this->rules = $product->getTypeInstance()->getTypeValidationRules();
        $this->rules['sku'] = ['required', 'unique:products,sku,'.$this->id, new Slug];
        $this->rules['status'] = ['boolean'];
        return $this->rules;
    }

    public function prepareForValidation()
    {
        if (isset($this->uniqueFields['values.common.sku']) || isset($this->values['common']['sku'])) {
            $this->merge(['sku' => $this->values['common']['sku']]);
        }
        $this->merge(['status' => (int) $this->status]);
    }
}
```

**Pattern:**
- Injectable repository for dynamic rule generation
- Uses `prepareForValidation()` to transform incoming data
- Delegates product-type-specific rules to product instance
- Merges transformed data back into request

---

### CategoryRequest

**File:** `packages/Webkul/Admin/src/Http/Requests/CategoryRequest.php`

```php
class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $uniqueRule = 'unique:categories,code';

        if (! empty($this->id)) {
            $uniqueRule .= ','.$this->id;
        }

        if ($this->id) {
            return [
                'code' => [$uniqueRule, new Code],
            ];
        }

        return [
            'code' => ['required', $uniqueRule, new Code],
        ];
    }
}
```

**Pattern:**
- Simple validation with conditional logic
- Requires `code` on creation, optional on update
- Uses custom `Code` rule validation

---

### MassDestroyRequest

**File:** `packages/Webkul/Admin/src/Http/Requests/MassDestroyRequest.php`

```php
class MassDestroyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'indices'   => ['required', 'array'],
            'indices.*' => ['integer'],
        ];
    }
}
```

**Pattern:**
- Validates array of IDs for batch operations
- Ensures each ID is an integer

---

### Other Form Requests

| Request Class | File | Purpose |
|--------------|------|---------|
| `AttributeOptionForm` | `Requests/AttributeOptionForm.php` | Attribute option validation |
| `BulkEditRequest` | `Requests/BulkEditRequest.php` | Bulk edit validation |
| `ConfigurationForm` | `Requests/ConfigurationForm.php` | System configuration validation |
| `MassUpdateRequest` | `Requests/MassUpdateRequest.php` | Mass update validation |
| `UserForm` | `Requests/UserForm.php` | User creation/update validation |
| `ProductForm` | `Requests/ProductForm.php` | Product creation/update validation |

---

## 5. API Controllers

### API Base Pattern

**File:** `packages/Webkul/AdminApi/src/Http/Controllers/API/ApiController.php`

**Response Trait Methods:**
```php
protected function successResponse(string $message = 'Operation completed successfully', int $code = 200, array $data = [])
protected function modelNotFoundResponse(string $message = 'Data not found.', int $code = 404)
protected function validateErrorResponse(mixed $validator, string $message = 'Validation failed.', int $code = 422)
protected function storeExceptionLog($e)
```

**Standard Response Format:**
```json
{
    "success": true,
    "message": "Operation message",
    "data": { }
}
```

---

### API AttributeController

**File:** `packages/Webkul/AdminApi/src/Http/Controllers/API/Catalog/AttributeController.php`

**Methods:**
```php
public function index(): JsonResponse
public function get(string $code): JsonResponse
public function store()
public function update(string $code)
public function getOptions(string $code)
public function storeOption(string $code)
public function updateOption(string $code)
```

**Validation:**
```php
$rules = [
    'type' => ['required', new AttributeTypes],
    'code' => ['required', 'unique:attributes,code', new Code, new NotSupportedAttributes],
    'swatch_type' => ['nullable', new SwatchTypes],
];

if (isset($requestData['validation']) && $requestData['validation']) {
    $rules['validation'] = [new ValidationTypes];
}
```

---

### API ProductController

**File:** `packages/Webkul/AdminApi/src/Http/Controllers/API/Catalog/ProductController.php`

**Key Methods:**
```php
protected function updateProduct(array $data, Product $product): Product
protected function sanitizeData($product, $attributes)
public function patchProduct(Product $product, array $data)
```

**Pattern - Update Flow:**
```php
// 1. Sanitize WYSIWYG textarea fields
$attributes = $product->getEditableAttributes()
    ->where('enable_wysiwyg', '==', 1)
    ->where('type', '==', 'textarea');

$data['values'] = $this->sanitizeData($data['values'], $attributes);

// 2. Set values via ValueSetter facade (handles common, locale, channel, channel-locale specific values)
ValueSetter::setCommon($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::COMMON_VALUES_KEY]);
ValueSetter::setLocaleSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::LOCALE_VALUES_KEY]);
ValueSetter::setChannelSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_VALUES_KEY]);
ValueSetter::setChannelLocaleSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_LOCALE_VALUES_KEY]);

// 3. Set associations
ValueSetter::setUpSellsAssociation(...);
ValueSetter::setCrossSellsAssociation(...);
ValueSetter::setRelatedAssociation(...);

// 4. Get consolidated values
$data['values'] = ValueSetter::getValues();
```

---

### Other API Controllers

| Controller | File | Purpose |
|-----------|------|---------|
| `AttributeFamilyController` | `API/Catalog/AttributeFamilyController.php` | Family CRUD via API |
| `AttributeGroupController` | `API/Catalog/AttributeGroupController.php` | Attribute group CRUD via API |
| `CategoryController` | `API/Catalog/CategoryController.php` | Category CRUD via API |
| `CategoryFieldController` | `API/Catalog/CategoryFieldController.php` | Category field CRUD via API |
| `SimpleProductController` | `API/Catalog/SimpleProductController.php` | Simple product CRUD via API |
| `ConfigurableProductController` | `API/Catalog/ConfigurableProductController.php` | Configurable product CRUD via API |
| `MediaFileController` | `API/Catalog/MediaFileController.php` | Media file uploads via API |
| `ChannelController` | `API/Settings/ChannelController.php` | Channel read via API |
| `CurrencyController` | `API/Settings/CurrencyController.php` | Currency read via API |
| `LocaleController` | `API/Settings/LocaleController.php` | Locale read via API |
| `ApiKeysController` | `Integrations/ApiKeysController.php` | API key management |

---

## 6. API Routes

### Admin API Base Routes

**File:** `packages/Webkul/AdminApi/src/Routes/admin-api.php`

```php
Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',
        'api.scope',
        'accept.json',
        'request.locale',
    ],
], function () {
    require 'V1/settings-routes.php';
    require 'V1/catalog-routes.php';
});
```

**Middleware Stack:**
- `auth:api` -- API authentication (OAuth/Bearer token)
- `api.scope` -- OAuth scope validation
- `accept.json` -- Enforce JSON Accept header
- `request.locale` -- Set requested locale

---

### V1 Catalog API Routes

**File:** `packages/Webkul/AdminApi/src/Routes/V1/catalog-routes.php`

```
Attribute Groups:
  GET    /v1/rest/attribute-groups          -> index
  GET    /v1/rest/attribute-groups/{code}   -> get
  POST   /v1/rest/attribute-groups          -> store
  PUT    /v1/rest/attribute-groups/{code}   -> update

Attributes:
  GET    /v1/rest/attributes                -> index
  GET    /v1/rest/attributes/{code}         -> get
  POST   /v1/rest/attributes                -> store
  PUT    /v1/rest/attributes/{code}         -> update
  GET    /v1/rest/attributes/{code}/options -> getOptions
  POST   /v1/rest/attributes/{code}/options -> storeOption
  PUT    /v1/rest/attributes/{code}/options -> updateOption

Families:
  GET    /v1/rest/families                  -> index
  GET    /v1/rest/families/{code}           -> get
  POST   /v1/rest/families                  -> store
  PUT    /v1/rest/families/{code}           -> update

Categories:
  GET    /v1/rest/categories                -> index
  GET    /v1/rest/categories/{code}         -> get
  POST   /v1/rest/categories                -> store
  PUT    /v1/rest/categories/{code}         -> update
  DELETE /v1/rest/categories/{code}         -> delete
  PATCH  /v1/rest/categories/{code}         -> partialUpdate

Category Fields:
  GET    /v1/rest/category-fields           -> index
  GET    /v1/rest/category-fields/{code}    -> get

Products (Simple):
  GET    /v1/rest/products                  -> index (SimpleProductController)
  GET    /v1/rest/products/{code}           -> get
  POST   /v1/rest/products                  -> store
  PUT    /v1/rest/products/{code}           -> update
  DELETE /v1/rest/products/{code}           -> delete
  PATCH  /v1/rest/products/{sku}            -> partialUpdate

Configurable Products:
  GET    /v1/rest/configrable-products              -> index
  GET    /v1/rest/configrable-products/{code}       -> get
  POST   /v1/rest/configrable-products              -> store
  PUT    /v1/rest/configrable-products/{code}       -> update
  PATCH  /v1/rest/configrable-products/{code}       -> partialUpdate

Media Files:
  POST   /v1/rest/media-files/category      -> storeCategoryMedia
  POST   /v1/rest/media-files/product       -> storeProductMedia
  POST   /v1/rest/media-files/swatch        -> storeSwatchMedia
```

---

### V1 Settings API Routes

**File:** `packages/Webkul/AdminApi/src/Routes/V1/settings-routes.php`

```
Locales:
  GET    /v1/rest/locales                   -> index
  GET    /v1/rest/locales/{code}            -> get

Channels:
  GET    /v1/rest/channels                  -> index
  GET    /v1/rest/channels/{code}           -> get

Currencies:
  GET    /v1/rest/currencies                -> index
  GET    /v1/rest/currencies/{code}         -> get
```

---

### Integration Routes

**File:** `packages/Webkul/AdminApi/src/Routes/integrations-routes.php`

Handles API key management UI routes for the admin panel.

---

## 7. Admin Routes Structure

### Catalog Routes

**File:** `packages/Webkul/Admin/src/Routes/catalog-routes.php`

**Pattern:**
```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('catalog')->group(function () {
        Route::controller(AttributeController::class)->prefix('attributes')->group(function () {
            Route::get('', 'index')->name('admin.catalog.attributes.index');
            Route::get('create', 'create')->name('admin.catalog.attributes.create');
            Route::post('create', 'store')->name('admin.catalog.attributes.store');
            Route::get('edit/{id}', 'edit')->name('admin.catalog.attributes.edit');
            Route::put('edit/{id}', 'update')->name('admin.catalog.attributes.update');
            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.attributes.delete');
            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.attributes.mass_delete');
        });
        // ... more resource groups
    });
});
```

**Common Route Patterns:**
- Middleware: `['admin']` group
- Prefix: `config('app.admin_url')` (typically `/admin`)
- RESTful naming: `create`, `store`, `edit`, `update`, `destroy`
- Mass operations: `mass-delete`, `mass-update`
- Search: dedicated `search` route
- Extra actions: `copy`, `sync`, `check-variant`, etc.

---

### Settings Routes

**File:** `packages/Webkul/Admin/src/Routes/settings-routes.php`

**Data Transfer Import/Export Pattern:**
```php
Route::prefix('data-transfer')->group(function () {
    Route::controller(ImportController::class)->prefix('imports')->group(function () {
        Route::get('', 'index')->name('admin.settings.data_transfer.imports.index');
        Route::get('create', 'create')->name('admin.settings.data_transfer.imports.create');
        Route::post('create', 'store')->name('admin.settings.data_transfer.imports.store');
        Route::get('edit/{id}', 'edit')->name('admin.settings.data_transfer.imports.edit');
        Route::get('import/{id}', 'importView')->name('admin.settings.data_transfer.imports.import-view');
        Route::get('validate/{id}', 'validateImport')->name('admin.settings.data_transfer.imports.validate');
        Route::put('import-now/{id}', 'importNow')->name('admin.settings.data_transfer.imports.import_now');
        Route::get('start/{id}', 'start')->name('admin.settings.data_transfer.imports.start');
        Route::get('stats/{id}/{state?}', 'stats')->name('admin.settings.data_transfer.imports.stats');
        Route::get('download-sample/{type?}', 'downloadSample')->name('admin.settings.data_transfer.imports.download_sample');
        Route::get('download/{id}', 'download')->name('admin.settings.data_transfer.imports.download');
        Route::get('download-error-report/{id}', 'downloadErrorReport')->name('admin.settings.data_transfer.imports.download_error_report');
    });
});
```

---

### Configuration Routes

**File:** `packages/Webkul/Admin/src/Routes/configuration-routes.php`

Handles system configuration pages (Magic AI config, general settings, etc.).

---

### Authentication Routes

**File:** `packages/Webkul/Admin/src/Routes/auth-routes.php`

Handles admin login, logout, password reset, and forgotten password flows.

---

### Notification Routes

**File:** `packages/Webkul/Admin/src/Routes/notification-routes.php`

Handles real-time notification fetching and management.

---

### REST Routes

**File:** `packages/Webkul/Admin/src/Routes/rest-routes.php`

Provides internal REST endpoints for the admin panel's Vue.js frontend (DataGrid, Select options, Column management, etc.).

---

## 8. HTTP Resources (API Response Transformers)

### AttributeResource

**File:** `packages/Webkul/Admin/src/Http/Resources/AttributeResource.php`

```php
class AttributeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'      => $this->id,
            'code'    => $this->code,
            'type'    => $this->type,
            'name'    => $this->admin_name,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
```

### AttributeOptionResource

**File:** `packages/Webkul/Admin/src/Http/Resources/AttributeOptionResource.php`

```php
class AttributeOptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'    => $this->id,
            'code'  => $this->code,
            'label' => $this->admin_name,
            // ... additional fields
        ];
    }
}
```

**Pattern:**
- Extends `JsonResource` for API responses
- Transforms model data into API-safe format
- Handles nested resources via `::collection()`
- Uses model properties directly or custom attributes (e.g. `admin_name` accessor)

---

## 9. ACL (Access Control List) Configuration

**File:** `packages/Webkul/Admin/src/Config/acl.php`

### Structure Per Entry

```php
[
    'key'   => 'catalog.products.create',
    'name'  => 'admin::app.acl.create',
    'route' => 'admin.catalog.products.store',
    'sort'  => 1,
]
```

### Full Permission Tree

```
Dashboard
  - dashboard

Catalog
  - catalog.products (view)
    - catalog.products.create
    - catalog.products.copy
    - catalog.products.edit
    - catalog.products.delete
    - catalog.products.mass_update
    - catalog.products.mass_delete

  - catalog.categories (view)
    - catalog.categories.create
    - catalog.categories.edit
    - catalog.categories.delete
    - catalog.categories.mass_delete

  - catalog.category_fields (view)
    - catalog.category_fields.create
    - catalog.category_fields.edit
    - catalog.category_fields.delete
    - catalog.category_fields.mass_update
    - catalog.category_fields.mass_delete

  - catalog.attributes (view)
    - catalog.attributes.create
    - catalog.attributes.edit
    - catalog.attributes.delete
    - catalog.attributes.mass_delete

  - catalog.attribute_groups (view)
    - catalog.attribute_groups.create
    - catalog.attribute_groups.edit
    - catalog.attribute_groups.delete

  - catalog.attribute_families (view)
    - catalog.attribute_families.create
    - catalog.attribute_families.edit
    - catalog.attribute_families.delete

Data Transfer
  - data_transfer.imports (view)
    - data_transfer.imports.create
    - data_transfer.imports.edit
    - data_transfer.imports.delete
    - data_transfer.imports.execute
  - data_transfer.exports (view)
    - data_transfer.exports.create
    - data_transfer.exports.edit
    - data_transfer.exports.delete
    - data_transfer.exports.execute

Settings
  - settings.locales
  - settings.currencies
  - settings.channels
  - settings.users
  - settings.roles

Configuration
  - configuration
```

**Pattern:**
- Hierarchical structure with parent/child relationships via dot-notation keys
- Maps to route names for authorization checks
- Translation keys for UI display (`admin::app.acl.*`)
- Sort order for rendering sequence

---

## 10. Menu Configuration

**File:** `packages/Webkul/Admin/src/Config/menu.php`

### Structure Per Entry

```php
[
    'key'        => 'catalog.products',
    'name'       => 'admin::app.components.layouts.sidebar.products',
    'route'      => 'admin.catalog.products.index',
    'sort'       => 1,
    'icon'       => 'icon-products',
]
```

### Full Menu Tree

```
Dashboard (sort: 1)
  - dashboard

Catalog (sort: 3)
  - catalog.products (sort: 1)
  - catalog.categories (sort: 2)
  - catalog.category_fields (sort: 3)
  - catalog.attributes (sort: 4)
  - catalog.attribute_groups (sort: 5)
  - catalog.families (sort: 6)

Data Transfer (sort: 8)
  - data_transfer.job_tracker (sort: 1)
  - data_transfer.imports (sort: 2)
  - data_transfer.export (sort: 3)

Settings (sort: 8)
  - settings.locales (sort: 1)
  - settings.currencies (sort: 2)
  - settings.channels (sort: 5)
  - settings.users (sort: 6)
  - settings.roles (sort: 7)

Configuration (sort: 9)
  - configuration.magic-ai (sort: 1)
```

**Pattern:**
- Hierarchical keys matching ACL keys (enables permission-based visibility)
- Translation keys for localized menu labels
- Route names for navigation links
- Sort order for display sequence
- Icon classes for sidebar rendering

---

## 11. DataGrid Pattern

**File:** `packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`

### Class Structure

```php
class ProductDataGrid extends DataGrid implements ExportableInterface
{
    use AttributeColumnTrait;

    protected $primaryColumn = 'product_id';
    protected $sortColumn = 'products.updated_at';
    protected $elasticSearchSortColumn = 'updated_at';
    protected $manageableColumn = true;
    protected $defaultColumns = [
        'sku', 'image', 'name', 'attribute_family', 'status', 'type', 'completeness',
    ];

    public function prepareQueryBuilder()
    {
        // Builds complex query with filters, joins, selections
    }

    public function addColumns()
    {
        // Defines grid columns with types, labels, sortability, filterability
    }

    public function prepareActions()
    {
        // Defines row actions (edit, delete, etc.)
    }

    public function prepareMassActions()
    {
        // Defines mass actions (mass delete, mass update)
    }
}
```

**Pattern:**
- Extends base `DataGrid` class
- Implements `ExportableInterface` for data export
- Uses `AttributeColumnTrait` for dynamic attribute columns
- Manages query building with filters, joins
- Supports ElasticSearch or database queries transparently
- AJAX-based rendering for the Vue.js frontend
- Manageable columns (user can show/hide columns)

### Other DataGrids

| DataGrid | File | Purpose |
|----------|------|---------|
| `ProductDataGrid` | `DataGrids/Catalog/ProductDataGrid.php` | Product listing |
| `CategoryDataGrid` | `DataGrids/Catalog/CategoryDataGrid.php` | Category listing |
| `AttributeDataGrid` | `DataGrids/Catalog/AttributeDataGrid.php` | Attribute listing |
| `AttributeFamilyDataGrid` | `DataGrids/Catalog/AttributeFamilyDataGrid.php` | Attribute family listing |
| `ChannelDataGrid` | `DataGrids/Settings/ChannelDataGrid.php` | Channel listing |
| `CurrencyDataGrid` | `DataGrids/Settings/CurrencyDataGrid.php` | Currency listing |
| `LocaleDataGrid` | `DataGrids/Settings/LocaleDataGrid.php` | Locale listing |
| `UserDataGrid` | `DataGrids/Settings/UserDataGrid.php` | User listing |
| `RoleDataGrid` | `DataGrids/Settings/RoleDataGrid.php` | Role listing |

---

## 12. Event System

### Admin EventServiceProvider

**File:** `packages/Webkul/Admin/src/Providers/EventServiceProvider.php`

```php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'admin.password.update.after' => [
            'Webkul\Admin\Listeners\Admin@afterPasswordUpdated',
        ],
    ];
}
```

### Event Naming Convention

All events follow the pattern:

```
[domain].[entity].[action].before
[domain].[entity].[action].after
```

### Catalog Events (Full List)

```
catalog.attribute.create.before
catalog.attribute.create.after
catalog.attribute.update.before
catalog.attribute.update.after
catalog.attribute.delete.before
catalog.attribute.delete.after

catalog.attribute_family.create.before
catalog.attribute_family.create.after
catalog.attribute_family.update.before
catalog.attribute_family.update.after
catalog.attribute_family.delete.before
catalog.attribute_family.delete.after

catalog.category.create.before
catalog.category.create.after
catalog.category.update.before
catalog.category.update.after
catalog.category.delete.before
catalog.category.delete.after

catalog.product.create.before
catalog.product.create.after
catalog.product.update.before
catalog.product.update.after
catalog.product.delete.before
catalog.product.delete.after
catalog.product.copy

products.datagrid.sync
```

### Core Events (Full List)

```
core.channel.create.before
core.channel.create.after
core.channel.update.before
core.channel.update.after
core.channel.delete.before
core.channel.delete.after

core.locale.create.before
core.locale.create.after
core.locale.update.before
core.locale.update.after
core.locale.delete.before
core.locale.delete.after

core.currency.create.before
core.currency.create.after
core.currency.update.before
core.currency.update.after
core.currency.delete.before
core.currency.delete.after
```

### User Events

```
user.role.create.before
user.role.create.after
user.role.update.before
user.role.update.after
user.role.delete.before
user.role.delete.after

admin.password.update.after
```

### Event Usage Pattern

```php
// Before event - dispatched BEFORE the operation (can be used to prevent it)
Event::dispatch('catalog.product.create.before');

// Perform the operation
$product = $this->productRepository->create($data);

// After event - dispatched AFTER the operation with the result
Event::dispatch('catalog.product.create.after', $product);
```

**Key characteristics:**
- `.before` events receive no arguments or the ID (for update/delete)
- `.after` events receive the created/updated model instance or the ID (for delete)
- Listeners can be registered to modify behavior or trigger side effects
- Events enable package extensibility without modifying core code

---

## 13. Middleware Pattern

### EnsureChannelLocaleIsValid

**File:** `packages/Webkul/Admin/src/Http/Middleware/EnsureChannelLocaleIsValid.php`

**Usage:** Applied to specific routes like product edit:
```php
Route::get('edit/{id}', 'edit')
    ->name('admin.catalog.products.edit')
    ->middleware(EnsureChannelLocaleIsValid::class);
```

**Pattern:**
- Route-specific middleware for validation
- Ensures requested channel and locale are valid
- Prevents 404 errors from invalid combinations

### Admin Middleware Group

The `admin` middleware group typically includes:
- Authentication check
- Session handling
- CSRF verification
- Locale resolution
- ACL authorization

---

## 14. Request/Response Flow

### Admin Controller Flow

```
1. HTTP Request (browser)
   |
2. Route matching (Routes/catalog-routes.php)
   |
3. Middleware execution (admin, auth, CSRF)
   |
4. Controller method invoked
   |
5. Dependency injection (repositories, validators)
   |
6. Validation via FormRequest or $this->validate()
   |
7. Event dispatch (*.before)
   |
8. Repository/Model operations
   |
9. Event dispatch (*.after)
   |
10. Session flash message OR JsonResponse
    |
11. Redirect (HTML) or JSON return (AJAX/DataGrid)
```

### API Controller Flow

```
1. HTTP Request with Bearer token
   |
2. API Route matching (/v1/rest/...)
   |
3. Middleware: auth:api, api.scope, accept.json, request.locale
   |
4. API Controller method invoked
   |
5. Validation via validator() helper
   |
6. Event dispatch (*.before)
   |
7. Repository operations
   |
8. Event dispatch (*.after)
   |
9. ApiResponse::successResponse() or error response
   |
10. JSON response with { success, message, data }
```

### DataGrid AJAX Flow

```
1. Vue.js component requests datagrid data (AJAX GET)
   |
2. DataGridController receives request
   |
3. Specific DataGrid class instantiated (e.g. ProductDataGrid)
   |
4. prepareQueryBuilder() builds query
   |
5. Filters, sorting, pagination applied
   |
6. addColumns() defines column structure
   |
7. prepareActions() defines row actions
   |
8. JSON response with { records, columns, actions, meta }
   |
9. Vue.js renders the table
```

---

## 15. Key Architectural Patterns Summary

| Pattern | Usage | Example |
|---------|-------|---------|
| **Constructor Injection** | All controllers inject repositories and services | `ProductController::__construct(ProductRepository, AttributeFamilyRepository)` |
| **Form Requests** | Input validation with preprocessing | `ProductForm`, `CategoryRequest` |
| **Events Before/After** | Side effects and extensibility | `catalog.product.create.before/after` |
| **DataGrid** | Dynamic, filterable, sortable tables | `ProductDataGrid` extends `DataGrid` |
| **API Resources** | Transform models to JSON | `AttributeResource` extends `JsonResource` |
| **Middleware** | Route-specific validation | `EnsureChannelLocaleIsValid` |
| **Repositories** | Data access abstraction | `ProductRepository::find()`, `update()`, `create()` |
| **Value Objects** | Complex attribute value handling | `ValueSetter` facade for channel/locale values |
| **ACL Configuration** | Hierarchical permissions | `Config/acl.php` with `key`, `name`, `route` |
| **Menu Configuration** | Sidebar navigation structure | `Config/menu.php` with `key`, `name`, `sort` |
| **ApiResponse Trait** | Standardized JSON responses | `successResponse()`, `modelNotFoundResponse()` |
| **Custom Validation Rules** | Domain-specific rules | `Code`, `Slug`, `SwatchTypes`, `NotSupportedAttributes` |
| **Mass Operations** | Batch actions on multiple records | `massDestroy()`, `massUpdate()` with array validation |
| **RESTful + Custom Routes** | Standard CRUD plus domain actions | `copy`, `sync`, `search`, `tree`, `check-variant` |
