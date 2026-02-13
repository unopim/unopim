# UnoPim APPLICATION Layer Skill

Use this skill when working with controllers, routes, ACL, menus, form requests, DataGrid implementations, or event dispatching.

## Admin Controller Pattern

```php
namespace Webkul\Admin\Http\Controllers\Catalog;

use Webkul\Admin\Http\Controllers\Controller;

class MyController extends Controller
{
    public function __construct(
        protected MyRepository $myRepository
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(MyDataGrid::class)->process();
        }
        return view('admin::my.index');
    }

    public function store()
    {
        Event::dispatch('my.entity.create.before');
        $entity = $this->myRepository->create(request()->validated());
        Event::dispatch('my.entity.create.after', $entity);
        session()->flash('success', trans('admin::app.my.created'));
        return redirect()->route('admin.my.index');
    }

    public function destroy(int $id)
    {
        Event::dispatch('my.entity.delete.before', $id);
        $this->myRepository->delete($id);
        Event::dispatch('my.entity.delete.after', $id);
        return new JsonResponse(['message' => trans('admin::app.my.deleted')]);
    }
}
```

## API Controller Pattern

```php
namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Webkul\AdminApi\Http\Controllers\API\ApiController;

class MyApiController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json($this->myRepository->getAll());
    }

    public function get(string $code): JsonResponse
    {
        $entity = $this->myRepository->findByField('code', $code)->first();
        return $entity ? response()->json($entity) : $this->modelNotFoundResponse();
    }

    public function store() { /* validate, create, successResponse() */ }
}
```

**Response helpers:** `successResponse()`, `modelNotFoundResponse()`, `validateErrorResponse()`
**ValueSetter facade** for API product updates: `ValueSetter::setCommon()`, `setLocaleSpecific()`, `setChannelSpecific()`, etc.

## Route Registration

### Admin Routes
```php
// File: packages/Webkul/Admin/src/Routes/{domain}-routes.php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('catalog')->group(function () {
        Route::controller(MyController::class)->prefix('my-entity')->group(function () {
            Route::get('', 'index')->name('admin.catalog.my_entity.index');
            Route::get('create', 'create')->name('admin.catalog.my_entity.create');
            Route::post('create', 'store')->name('admin.catalog.my_entity.store');
            Route::get('edit/{id}', 'edit')->name('admin.catalog.my_entity.edit');
            Route::put('edit/{id}', 'update')->name('admin.catalog.my_entity.update');
            Route::delete('edit/{id}', 'destroy')->name('admin.catalog.my_entity.delete');
            Route::post('mass-delete', 'massDestroy')->name('admin.catalog.my_entity.mass_delete');
        });
    });
});
```

### API Routes
```php
// File: packages/Webkul/AdminApi/src/Routes/V1/catalog-routes.php
// Wrapped in: prefix 'v1/rest', middleware ['auth:api', 'api.scope', 'accept.json', 'request.locale']
Route::controller(MyApiController::class)->prefix('my-entities')->group(function () {
    Route::get('', 'index')->name('admin.api.my_entities.index');
    Route::get('{code}', 'get')->name('admin.api.my_entities.get');
    Route::post('', 'store')->name('admin.api.my_entities.store');
    Route::put('{code}', 'update')->name('admin.api.my_entities.update');
});
```

**Route naming:** Admin = `admin.{module}.{resource}.{action}`, API = `admin.api.{resource}.{action}`

## ACL Permission Tree

```
dashboard
catalog.products         (.create, .copy, .edit, .delete, .mass_update, .mass_delete)
catalog.categories       (.create, .edit, .delete, .mass_delete)
catalog.category_fields  (.create, .edit, .delete, .mass_update, .mass_delete)
catalog.attributes       (.create, .edit, .delete, .mass_delete)
catalog.attribute_groups  (.create, .edit, .delete)
catalog.attribute_families (.create, .edit, .delete)
data_transfer.imports    (.create, .edit, .delete, .execute)
data_transfer.exports    (.create, .edit, .delete, .execute)
settings.locales | settings.currencies | settings.channels | settings.users | settings.roles
configuration
```

### Adding a new ACL entry
```php
// In packages/Webkul/Admin/src/Config/acl.php
[
    'key'   => 'catalog.my_entity.create',
    'name'  => 'admin::app.acl.create',
    'route' => 'admin.catalog.my_entity.store',
    'sort'  => 1,
]
```

## Form Request Pattern

```php
class ProductForm extends FormRequest
{
    public function __construct(protected ProductRepository $productRepository) {}

    public function rules()
    {
        $product = $this->productRepository->find($this->id);
        $this->rules = $product->getTypeInstance()->getTypeValidationRules();
        $this->rules['sku'] = ['required', 'unique:products,sku,'.$this->id, new Slug];
        return $this->rules;
    }

    public function prepareForValidation()
    {
        $this->merge(['sku' => $this->values['common']['sku']]);
    }
}
```

**Custom rules:** `Code`, `Slug`, `AttributeTypes`, `SwatchTypes`, `NotSupportedAttributes`, `ConvertToArrayIfNeeded`

## Event Naming Convention

Pattern: `{domain}.{entity}.{action}.{before|after}`

```php
// Products
Event::dispatch('catalog.product.create.before');
Event::dispatch('catalog.product.create.after', $product);
Event::dispatch('catalog.product.update.before', $id);
Event::dispatch('catalog.product.update.after', $product);
Event::dispatch('catalog.product.delete.before', $id);
Event::dispatch('catalog.product.delete.after', $id);

// Categories
Event::dispatch('catalog.category.create.before');
Event::dispatch('catalog.category.create.after', $category);

// Channels
Event::dispatch('core.channel.create.before');
Event::dispatch('core.channel.create.after', $channel);
```

## HTTP Resources (API Response)

```php
class AttributeResource extends JsonResource
{
    public function toArray($request)
    {
        return ['id' => $this->id, 'code' => $this->code, 'type' => $this->type,
                'options' => AttributeOptionResource::collection($this->options)];
    }
}
```

## Key Rules

- Admin controllers extend `Webkul\Admin\Http\Controllers\Controller`
- API controllers extend `Webkul\AdminApi\Http\Controllers\API\ApiController`
- ALWAYS dispatch before/after events around create/update/delete operations
- ALWAYS use `['middleware' => ['admin']]` for admin routes
- API routes use middleware: `['auth:api', 'api.scope', 'accept.json', 'request.locale']`
- ACL keys use dot-notation matching route names
- Form Requests can inject repositories for dynamic validation rules
- DataGrid index methods: return `datagrid(MyDataGrid::class)->process()` for AJAX, view otherwise
- Mass operations need `MassDestroyRequest` validation (indices array of integers)
