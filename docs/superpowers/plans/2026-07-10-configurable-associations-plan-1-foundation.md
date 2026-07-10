# Configurable Associations — Plan 1: Type & Field Foundation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a DB-backed "Association Types" admin section where merchants create/edit/delete association types, each owning custom fields (Category-field style), with the 3 legacy types seeded as non-user-defined defaults.

**Architecture:** Mirror the `CategoryField` system, but scope fields under a parent `association_type` (FK) instead of being global. Definition models/repos/migrations/config/rules live in `packages/Webkul/Product`; admin UI (controller, DataGrid, blades, routes, menu, ACL) lives in `packages/Webkul/Admin`. No product-link storage yet — that is Plan 2.

**Tech Stack:** Laravel 12, Concord, Pest 3, Blade + Vue 3, Laravel Pint.

## Global Constraints

- PHP 8.3+, **Laravel 13** (repo upgraded — commit `43d2e1b7`). Use Laravel 13 best practices. Repository pattern (no Eloquent in controllers). Concord proxy models.
- **Component-first, no raw markup.** Never handcraft `<input>/<select>/<textarea>/<label>` in a blade. First check for an existing `x-admin::*` component; if none fits, CREATE a reusable Blade component (`packages/Webkul/Admin/src/Resources/views/components/...`), then consume it. Reuse the category `dynamic-fields` / field-builder components where they already do the job rather than duplicating.
- **No custom one-off code.** Follow established UnoPim patterns/standards; reuse existing rules, repositories, traits, and components. Only add new code where the codebase has no equivalent.
- **Extendability (PIM framework).** Everything must be overridable by third-party packages: use Concord proxy models (never reference concrete models directly), keep field types in the `association_field_types` config (mergeable/extendable), and fire events around type/field/association mutation (`association_type.create.before/after`, etc.) mirroring how Product/Category fire events, so plugins can hook without patching core.
- **Performance.** Add DB indexes on all lookup/FK/status/position columns (done in migrations). Eager-load `translations`/`fields`/`options` to avoid N+1 (repos use `with(...)`). Prefer keyed collections/`DB::table` for grid queries. No per-row queries in loops.
- **Security.** Every controller action ACL-gated via `bouncer()`. All input via type-hinted FormRequest classes (never inline `$request->validate()`). Guard mass-assignment (`$fillable` only). Parameterize all DataGrid queries — no string interpolation into SQL. Escape all blade output. Defaults (`is_user_defined = 0`) protected from delete/code-change server-side (not just UI).
- Repository pattern (no Eloquent in controllers). Concord proxy models.
- Tables are created WITHOUT the `wk_` prefix in migration code — Laravel adds the configured prefix automatically (verify: Category migrations use bare `category_fields`). Use bare names: `association_types`, `association_type_fields`, etc.
- Migrations go in `packages/Webkul/Product/src/Database/Migrations/`.
- Every user-facing string uses `trans('...')`/`@lang`. Add keys in `en_US` first, then propagate to ALL 33 locales with natural translations; keep `:param` placeholders intact. Run `php artisan unopim:translations:check` — zero errors.
- Route middleware `['admin']` only. Controllers return `JsonResponse` with `redirect_url` + `message` for store/update/delete.
- After EVERY php change: `vendor/bin/pint` then `vendor/bin/pint --test` (zero issues).
- Models with audit use `HistoryTrait` + `PresentableHistoryInterface`/`HistoryContract` where the Category equivalent does.
- Reuse the existing `Code` and `ValidationTypes` rules from `Webkul\Core`/`Webkul\Category` where the Category field CRUD uses them — do NOT duplicate.

## Reference sources (copy-and-adapt targets)

For clone-heavy boilerplate, copy the named source file and apply the substitution list in the task. Substitution list **S** (apply to every copied file unless the task overrides):

| From (Category) | To (Association) |
|---|---|
| `category_field_option_translations` | `association_type_field_option_translations` |
| `category_field_options` | `association_type_field_options` |
| `category_field_translations` | `association_type_field_translations` |
| `category_fields` | `association_type_fields` |
| `CategoryFieldOptionTranslation` | `AssociationTypeFieldOptionTranslation` |
| `CategoryFieldOption` | `AssociationTypeFieldOption` |
| `CategoryFieldTranslation` | `AssociationTypeFieldTranslation` |
| `CategoryField` | `AssociationTypeField` |
| `Webkul\Category` namespace | `Webkul\Product` namespace |
| `category_field_types` (config) | `association_field_types` |
| `admin.catalog.category_fields.*` (routes) | `admin.catalog.association_types.*` |
| `additional_data` unique path `categories,...` | (N/A in Plan 1 — fields have no values yet) |

Reference files (verified present):
- Migrations: `packages/Webkul/Category/src/Database/Migrations/2024_05_15_204004_create_category_fields.php` (+ `_204039`, `_204056`, `_204106`).
- Model: `packages/Webkul/Category/src/Models/CategoryField.php`.
- Repo: `packages/Webkul/Category/src/Repositories/CategoryFieldRepository.php` and `CategoryFieldOptionRepository.php`.
- Config: `packages/Webkul/Category/src/Config/category_field_types.php`.
- Rules: `packages/Webkul/Category/src/Rules/{FieldTypes,NotSupportedFields,ValidationTypes,FieldOption}.php`.
- Admin controller: `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryFieldController.php`.
- DataGrid: `packages/Webkul/Admin/src/DataGrids/Catalog/CategoryFieldDataGrid.php`.
- Blades: `packages/Webkul/Admin/src/Resources/views/catalog/categories/field/{index,create,edit}.blade.php`.
- Wiring: `packages/Webkul/Category/src/Providers/CategoryServiceProvider.php`, `packages/Webkul/Admin/src/Routes/catalog-routes.php` (L120-142), `packages/Webkul/Admin/src/Config/menu.php` (L37-39), `packages/Webkul/Admin/src/Config/acl.php` (L144-181).

---

### Task 1: Migrations — type + field definition tables

**Files:**
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100001_create_association_types_table.php`
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100002_create_association_type_translations_table.php`
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100003_create_association_type_fields_table.php`
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100004_create_association_type_field_translations_table.php`
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100005_create_association_type_field_options_table.php`
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100006_create_association_type_field_option_translations_table.php`

**Interfaces:**
- Produces tables: `association_types(id, code UNIQUE, status, position, is_user_defined, timestamps)`, `association_type_translations(id, association_type_id FK, locale, name)`, `association_type_fields(id, association_type_id FK, code, type, validation, position, is_required, is_unique, status, section, value_per_locale, enable_wysiwyg, regex_pattern, timestamps)` with `UNIQUE(code, association_type_id)`, `association_type_field_translations`, `association_type_field_options(id, code, sort_order, association_type_field_id FK)` `UNIQUE(code, association_type_field_id)`, `association_type_field_option_translations`.

- [ ] **Step 1: Write `create_association_types_table`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->boolean('status')->default(1);
            $table->integer('position')->nullable();
            $table->boolean('is_user_defined')->default(1);
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_types');
    }
};
```

- [ ] **Step 2: Write `create_association_type_translations_table`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_type_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_id')->unsigned();
            $table->string('locale');
            $table->string('name')->nullable();

            $table->unique(['association_type_id', 'locale']);
            $table->foreign('association_type_id')->references('id')->on('association_types')->onDelete('cascade');
            $table->index('association_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_type_translations');
    }
};
```

- [ ] **Step 3: Write `create_association_type_fields_table`** (clone of `create_category_fields.php` + `association_type_id` FK, and unique(code, association_type_id) instead of unique(code))

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_type_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('association_type_id')->unsigned();
            $table->string('code');
            $table->string('type');
            $table->string('validation')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('is_required')->default(0);
            $table->boolean('is_unique')->default(0);
            $table->boolean('status')->default(1);
            $table->string('section', 10)->default('left');
            $table->boolean('value_per_locale')->default(0);
            $table->boolean('enable_wysiwyg')->default(0);
            $table->string('regex_pattern')->nullable();
            $table->timestamps();

            $table->unique(['code', 'association_type_id'], 'unique_code_association_type_id');
            $table->foreign('association_type_id')->references('id')->on('association_types')->onDelete('cascade');
            $table->index('code');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_type_fields');
    }
};
```

- [ ] **Step 4: Write `create_association_type_field_translations_table`** — copy `2024_05_15_204039_create_category_fields_translations.php`, apply substitution **S** (table `association_type_field_translations`, column `association_type_field_id`, FK → `association_type_fields`). Keep `name` as `text` nullable.

- [ ] **Step 5: Write `create_association_type_field_options_table`** — copy `2024_05_15_204056_create_category_fields_options.php`, apply **S**: table `association_type_field_options`, column `association_type_field_id`, unique index name `unique_code_assoc_field_id`, FK → `association_type_fields`.

- [ ] **Step 6: Write `create_association_type_field_option_translations_table`** — copy `2024_05_15_204106_create_category_fields_options_translations.php`, apply **S**: table `association_type_field_option_translations`, column `association_type_field_option_id`, FK name `fk_assoc_field_opt_translations`, FK → `association_type_field_options`.

- [ ] **Step 7: Run migrations**

Run: `php artisan migrate`
Expected: 6 new tables created, no errors. Verify: `php artisan tinker --execute="echo Schema::hasTable('association_type_fields') ? 'ok' : 'missing';"` → `ok`

- [ ] **Step 8: Commit**

```bash
git add packages/Webkul/Product/src/Database/Migrations/2026_07_10_1000*
git commit -m "feat(product): association type + field definition tables"
```

---

### Task 2: Contracts, Models & Proxies

**Files:**
- Create: `packages/Webkul/Product/src/Contracts/AssociationType.php`, `AssociationTypeTranslation.php`, `AssociationTypeField.php`, `AssociationTypeFieldTranslation.php`, `AssociationTypeFieldOption.php`, `AssociationTypeFieldOptionTranslation.php`
- Create models under `packages/Webkul/Product/src/Models/`: `AssociationType.php`, `AssociationTypeProxy.php`, `AssociationTypeTranslation.php` (+Proxy), `AssociationTypeField.php` (+Proxy), `AssociationTypeFieldTranslation.php` (+Proxy), `AssociationTypeFieldOption.php` (+Proxy), `AssociationTypeFieldOptionTranslation.php` (+Proxy)
- Test: `packages/Webkul/Product/tests/... ` (use existing Product suite location; see Step 1)

**Interfaces:**
- Produces: `AssociationType` model — `$translatedAttributes = ['name']`, `$fillable = ['code','status','position','is_user_defined']`, relation `fields(): HasMany` → `AssociationTypeFieldProxy`. `AssociationTypeField` model — same shape as `CategoryField` minus `NON_DELETABLE_FIELD_CODE` logic, `$fillable` adds `association_type_id`, plus `associationType(): BelongsTo`, `options(): HasMany`, and the `getValidationsField()/getValidationsFieldWithOutMedia()/getJsonPath()` methods (Plan 3 uses them — copy verbatim from `CategoryField`, changing the unique-path table reference from `categories` to `product_associations` in `getValidationUniqueField()`/`getValidationRules()`).

- [ ] **Step 1: Write failing test for the AssociationType model + fields relation**

Create `packages/Webkul/Product/tests/Feature/AssociationTypeModelTest.php`:

```php
<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\AssociationTypeField;

it('creates an association type with a translated name and a field', function () {
    $type = AssociationType::create([
        'code'            => 'bundle_kit',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
    ]);

    $type->translations()->create(['locale' => 'en_US', 'name' => 'Bundle / Kit']);

    $field = AssociationTypeField::create([
        'association_type_id' => $type->id,
        'code'                => 'quantity',
        'type'                => 'text',
        'validation'          => 'number',
        'is_required'         => 1,
        'status'              => 1,
        'section'             => 'left',
    ]);

    expect($type->fresh()->name)->toBe('Bundle / Kit')
        ->and($type->fields)->toHaveCount(1)
        ->and($field->associationType->id)->toBe($type->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest --filter=AssociationTypeModelTest`
Expected: FAIL — "Class Webkul\Product\Models\AssociationType not found".

- [ ] **Step 3: Write the contracts** (interfaces, one per model). Example `packages/Webkul/Product/src/Contracts/AssociationType.php`:

```php
<?php

namespace Webkul\Product\Contracts;

interface AssociationType {}
```

Repeat for the other five contract interfaces (empty marker interfaces, matching how `Webkul\Category\Contracts\CategoryField` is used).

- [ ] **Step 4: Write `AssociationType` model** `packages/Webkul/Product/src/Models/AssociationType.php`:

```php
<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Contracts\AssociationType as AssociationTypeContract;

class AssociationType extends TranslatableModel implements AssociationTypeContract
{
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'code',
        'status',
        'position',
        'is_user_defined',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(AssociationTypeFieldProxy::modelClass())->orderBy('position');
    }
}
```

- [ ] **Step 5: Write `AssociationTypeTranslation` model**

```php
<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\AssociationTypeTranslation as AssociationTypeTranslationContract;

class AssociationTypeTranslation extends Model implements AssociationTypeTranslationContract
{
    public $timestamps = false;

    protected $fillable = ['locale', 'name'];
}
```

- [ ] **Step 6: Write `AssociationTypeField` model** — copy `packages/Webkul/Category/src/Models/CategoryField.php`, apply substitution **S**, then: remove `NON_DELETABLE_FIELD_CODE` + `canBeDeleted()` + `$historyFields` root_category_id (set `$historyTags = ['association_type_field']`); add `'association_type_id'` to `$fillable`; add relation:

```php
public function associationType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(AssociationTypeProxy::modelClass());
}
```

In `getValidationUniqueField()`, `getValidationRules()`, and any `unique:categories,additional_data->...` string, replace `categories` with `product_associations` (values live on the link row — Plan 2/3 consume this). Keep `getJsonPath()` unchanged.

- [ ] **Step 7: Write `AssociationTypeFieldOption`, `AssociationTypeFieldTranslation`, `AssociationTypeFieldOptionTranslation` models** — copy the matching Category models (`CategoryFieldOption`, `CategoryFieldTranslation`, `CategoryFieldOptionTranslation`), apply **S**. `AssociationTypeFieldOption`: `$timestamps = false`, `$translatedAttributes = ['label']`, `field(): BelongsTo → AssociationTypeFieldProxy`.

- [ ] **Step 8: Write all Proxy classes** — one per model, mirroring `CategoryFieldProxy`. Example:

```php
<?php

namespace Webkul\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class AssociationTypeProxy extends ModelProxy {}
```

- [ ] **Step 9: Register models in Concord** — in `packages/Webkul/Product/src/Providers/ModuleServiceProvider.php` (verify filename; Product package's Concord module provider) add the 6 models to the `$models` array. Confirm pattern by reading how `CategoryField` is registered in `packages/Webkul/Category/src/Providers/ModuleServiceProvider.php`.

- [ ] **Step 10: Run test to verify it passes**

Run: `vendor/bin/pest --filter=AssociationTypeModelTest`
Expected: PASS.

- [ ] **Step 11: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/{Contracts,Models} packages/Webkul/Product/tests/Feature/AssociationTypeModelTest.php packages/Webkul/Product/src/Providers/ModuleServiceProvider.php
git commit -m "feat(product): association type + field models"
```

---

### Task 3: Config + validation rules

**Files:**
- Create: `packages/Webkul/Product/src/Config/association_field_types.php`
- Create: `packages/Webkul/Product/src/Rules/AssociationFieldTypes.php`, `AssociationNotSupportedFields.php`
- Modify: `packages/Webkul/Product/src/Providers/ProductServiceProvider.php` — `mergeConfigFrom(... 'association_field_types')`
- Test: `packages/Webkul/Product/tests/Feature/AssociationFieldTypesRuleTest.php`

**Interfaces:**
- Produces: config key `association_field_types` (same 10 types as `category_field_types`). Rules `AssociationFieldTypes` (value must be a key of the config) and `AssociationNotSupportedFields` (reject codes `code`, `type`, `locale`).

- [ ] **Step 1: Write failing test**

```php
<?php

use Webkul\Product\Rules\AssociationFieldTypes;

it('rejects an unknown field type', function () {
    $failed = false;
    (new AssociationFieldTypes)->validate('type', 'not_a_type', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

it('accepts a known field type', function () {
    $failed = false;
    (new AssociationFieldTypes)->validate('type', 'text', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeFalse();
});
```

- [ ] **Step 2: Run test — fails** (`vendor/bin/pest --filter=AssociationFieldTypesRuleTest`) — "class not found".

- [ ] **Step 3: Write config** — copy `packages/Webkul/Category/src/Config/category_field_types.php` verbatim to `packages/Webkul/Product/src/Config/association_field_types.php` (the `name` translation keys reuse `admin::app.catalog.attributes.create.*` — leave as-is).

- [ ] **Step 4: Write `AssociationFieldTypes` rule** — copy `FieldTypes.php`, apply **S**, change `config('category_field_types')` → `config('association_field_types')`.

- [ ] **Step 5: Write `AssociationNotSupportedFields` rule** — copy `NotSupportedFields.php`, apply **S**, set `FILED_CODES = ['code', 'type', 'locale']`.

- [ ] **Step 6: Register config** — in `ProductServiceProvider::register()` add:

```php
$this->mergeConfigFrom(
    dirname(__DIR__).'/Config/association_field_types.php',
    'association_field_types'
);
```

- [ ] **Step 7: Run test — passes.**

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/{Config,Rules} packages/Webkul/Product/src/Providers/ProductServiceProvider.php packages/Webkul/Product/tests/Feature/AssociationFieldTypesRuleTest.php
git commit -m "feat(product): association field types config + rules"
```

---

### Task 4: Repositories

**Files:**
- Create: `packages/Webkul/Product/src/Repositories/AssociationTypeRepository.php`, `AssociationTypeFieldRepository.php`, `AssociationTypeFieldOptionRepository.php`
- Test: `packages/Webkul/Product/tests/Feature/AssociationTypeRepositoryTest.php`

**Interfaces:**
- Consumes: models from Task 2.
- Produces:
  - `AssociationTypeFieldOptionRepository extends Repository` (`model(): AssociationTypeFieldOption::class`) — thin, mirror `CategoryFieldOptionRepository`.
  - `AssociationTypeFieldRepository extends Repository` — mirror `CategoryFieldRepository` (`$fieldWithOptions = ['select','multiselect','checkbox']`, `create()`/`update()` persist options, `getActiveFields()`), constructor injects `AssociationTypeFieldOptionRepository`.
  - `AssociationTypeRepository extends Repository` (`model(): AssociationType::class`) — `create(array): AssociationType` persists translations + nested `fields` (via field repo); `update(array,$id)`; `getActiveTypes(): Collection` = `where(['status'=>1])->with(['translations','fields'])->orderBy('position')->get()`.

- [ ] **Step 1: Write failing test**

```php
<?php

use Webkul\Product\Repositories\AssociationTypeRepository;

it('creates a type with translations and a field via the repository', function () {
    $repo = app(AssociationTypeRepository::class);

    $type = $repo->create([
        'code'            => 'spare_parts',
        'status'          => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Spare Parts'],
        'fields'          => [
            ['code' => 'position', 'type' => 'text', 'validation' => 'number', 'status' => 1, 'section' => 'left', 'en_US' => ['name' => 'Position']],
        ],
    ]);

    expect($type->code)->toBe('spare_parts')
        ->and($repo->getActiveTypes())->toHaveCount(fn ($c) => $c >= 1)
        ->and($type->fields)->toHaveCount(1);
});
```

*(Note: if the codebase's `TranslatableModel` create-with-translations convention differs from the `en_US => [...]` array shape, follow whatever `CategoryFieldRepository`/`CategoryRepository` uses — read them and match. Adjust the test payload to the real convention before Step 3.)*

- [ ] **Step 2: Run test — fails** (`vendor/bin/pest --filter=AssociationTypeRepositoryTest`).

- [ ] **Step 3: Write `AssociationTypeFieldOptionRepository`** — copy `CategoryFieldOptionRepository`, apply **S**.

- [ ] **Step 4: Write `AssociationTypeFieldRepository`** — copy `CategoryFieldRepository`, apply **S**; rename `getActiveCategoryFieldsBySection`→`getActiveFieldsBySection`, `getActiveCategoryFields`→`getActiveFields`; keep option-persistence logic; ensure `create()`/`update()` forward `association_type_id` from `$data`.

- [ ] **Step 5: Write `AssociationTypeRepository`** — `model()` returns `AssociationType::class`; `create()` calls `parent::create()`, saves translation rows, and for each entry in `$data['fields']` calls the field repo `create()` with `association_type_id`; `update()` mirrors with new/delete/update handling like `CategoryFieldRepository::update`; add `getActiveTypes()`.

- [ ] **Step 6: Run test — passes.**

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Repositories packages/Webkul/Product/tests/Feature/AssociationTypeRepositoryTest.php
git commit -m "feat(product): association type repositories"
```

---

### Task 5: Seed the 3 default types

**Files:**
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_10_100007_seed_default_association_types.php`
- Test: `packages/Webkul/Product/tests/Feature/DefaultAssociationTypesSeedTest.php`

**Interfaces:**
- Consumes: `association_types` + `association_type_translations` tables.
- Produces: 3 rows `related_products`, `up_sells`, `cross_sells` with `is_user_defined = 0`, each with an `en_US` translation. Idempotent (uses `updateOrInsert` on `code`).

- [ ] **Step 1: Write failing test**

```php
<?php

use Webkul\Product\Models\AssociationType;

it('seeds the three default association types as non-user-defined', function () {
    foreach (['related_products', 'up_sells', 'cross_sells'] as $code) {
        $type = AssociationType::where('code', $code)->first();
        expect($type)->not->toBeNull()
            ->and((bool) $type->is_user_defined)->toBeFalse();
    }
});
```

- [ ] **Step 2: Run test — fails** (rows not present in a fresh migrate).

- [ ] **Step 3: Write the seed migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'related_products' => 'Related Products',
            'up_sells'         => 'Up Sells',
            'cross_sells'      => 'Cross Sells',
        ];

        $position = 1;

        foreach ($defaults as $code => $name) {
            DB::table('association_types')->updateOrInsert(
                ['code' => $code],
                ['status' => 1, 'position' => $position++, 'is_user_defined' => 0, 'updated_at' => now(), 'created_at' => now()]
            );

            $id = DB::table('association_types')->where('code', $code)->value('id');

            DB::table('association_type_translations')->updateOrInsert(
                ['association_type_id' => $id, 'locale' => 'en_US'],
                ['name' => $name]
            );
        }
    }

    public function down(): void
    {
        DB::table('association_types')->whereIn('code', ['related_products', 'up_sells', 'cross_sells'])->delete();
    }
};
```

- [ ] **Step 4: Migrate + run test — passes.**

Run: `php artisan migrate && vendor/bin/pest --filter=DefaultAssociationTypesSeedTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Product/src/Database/Migrations/2026_07_10_100007_seed_default_association_types.php packages/Webkul/Product/tests/Feature/DefaultAssociationTypesSeedTest.php
git commit -m "feat(product): seed default association types"
```

---

### Task 6: Admin DataGrid

**Files:**
- Create: `packages/Webkul/Admin/src/DataGrids/Catalog/AssociationTypeDataGrid.php`
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/AssociationTypeDataGridTest.php` (follow existing Admin DataGrid test pattern)

**Interfaces:**
- Consumes: `association_types` + `association_type_translations`.
- Produces: DataGrid columns `code`, `name` (joined translation for requested locale), `status`, `position`; edit + delete actions; mass delete + mass status update. Delete/mass actions must guard `is_user_defined = 0` rows (defaults cannot be deleted).

- [ ] **Step 1: Write failing test** — assert the grid returns seeded rows and that a default type row exposes no delete action (or a disabled one). Model on an existing Admin DataGrid test in `packages/Webkul/Admin/tests/`.

- [ ] **Step 2: Run test — fails.**

- [ ] **Step 3: Write DataGrid** — copy `packages/Webkul/Admin/src/DataGrids/Catalog/CategoryFieldDataGrid.php`; `prepareQueryBuilder()` uses `DB::table('association_types')` left-joined to `association_type_translations` on requested locale; drop the `type` column, keep `code/name/status/position`; in `prepareActions()` add edit always, and delete only `if (! $row->is_user_defined)`. Use `bouncer()->hasPermission('catalog.association_types.delete')` guards.

- [ ] **Step 4: Run test — passes.**

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Admin/src/DataGrids/Catalog/AssociationTypeDataGrid.php packages/Webkul/Admin/tests/Feature/Catalog/AssociationTypeDataGridTest.php
git commit -m "feat(admin): association type datagrid"
```

---

### Task 7: Admin controller + routes + ACL + menu

**Files:**
- Create: `packages/Webkul/Admin/src/Http/Controllers/Catalog/AssociationTypeController.php`
- Create: `packages/Webkul/Admin/src/Http/Requests/AssociationTypeRequest.php` (FormRequest — holds all validation; **no inline `$request->validate()`**)
- Modify: `packages/Webkul/Admin/src/Routes/catalog-routes.php` (add block after the category-fields block, L~142)
- Modify: `packages/Webkul/Admin/src/Config/acl.php` (add `catalog.association_types.*` entries mirroring `catalog.category_fields`)
- Modify: `packages/Webkul/Admin/src/Config/menu.php` (add `catalog.association_types` under catalog)
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/AssociationTypeControllerTest.php`

**Interfaces:**
- Consumes: `AssociationTypeRepository`, `AssociationTypeDataGrid`.
- Produces: routes `admin.catalog.association_types.{index,create,store,edit,update,delete,mass_delete,mass_update}`. `store()`/`update()` return `JsonResponse` with `redirect_url` + `message`. `update()`/`delete()` reject `is_user_defined = 0` types for code changes/deletion (code + `is_user_defined` immutable on defaults; a default's fields ARE editable).

- [ ] **Step 1: Write failing feature test** — authenticated admin can `POST admin.catalog.association_types.store` with `code`, `en_US.name`, and a `fields` array, and the row + field persist; and cannot delete a default type (expect error response). Model on `packages/Webkul/Admin/tests/Feature/Catalog/` category-field tests.

- [ ] **Step 2: Run test — fails.**

- [ ] **Step 3: Write the FormRequest** `AssociationTypeRequest` (type-hinted into `store`/`update` — no inline validation). Rules: `code` → `['required', new Code, new AssociationNotSupportedFields, Rule::unique('association_types','code')->ignore($this->route('id'))]`, `fields.*.code` → `['required', new AssociationNotSupportedFields]`, `fields.*.type` → `['required', new AssociationFieldTypes]`, `fields.*.validation` → reuse the existing `ValidationTypes` rule, per-locale `name` required. In `update` context, drop `code`/`is_user_defined` from the validated set (immutable on defaults). Reuse the same request for both actions.

- [ ] **Step 4: Write controller** — copy `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryFieldController.php`; type-hint `AssociationTypeRequest` into `store()`/`update()`; swap repos to `AssociationTypeRepository`/`AssociationTypeFieldRepository`; ACL-gate every action with `bouncer()->hasPermission('catalog.association_types.*')`; fire `association_type.create.before/after`, `.update.*`, `.delete.*` events around repo calls (mirror Product/Category event names); `index()` returns the index view (DataGrid rendered in blade); on `update()` exclude `code` + `is_user_defined`; `destroy()`/`massDestroy()` abort with a translated error when a target is `! is_user_defined`.

- [ ] **Step 5: Add routes** to `catalog-routes.php`:

```php
Route::controller(AssociationTypeController::class)->prefix('catalog/association-types')->group(function () {
    Route::get('', 'index')->name('admin.catalog.association_types.index');
    Route::get('create', 'create')->name('admin.catalog.association_types.create');
    Route::post('create', 'store')->name('admin.catalog.association_types.store');
    Route::get('edit/{id}', 'edit')->name('admin.catalog.association_types.edit');
    Route::put('edit/{id}', 'update')->name('admin.catalog.association_types.update');
    Route::delete('{id}', 'destroy')->name('admin.catalog.association_types.delete');
    Route::post('mass-delete', 'massDestroy')->name('admin.catalog.association_types.mass_delete');
    Route::post('mass-update', 'massUpdate')->name('admin.catalog.association_types.mass_update');
});
```

(Import the controller at the top of the file. Confirm the file's existing `Route::group(['middleware' => ['admin']...])` wrapper — place inside it.)

- [ ] **Step 6: Add ACL** — mirror the `catalog.category_fields` block in `acl.php` with key `catalog.association_types` and sub-permissions `create/edit/delete` (flat arrays, no nested `children`).

- [ ] **Step 7: Add menu** — mirror `menu.php` L37-39 category_fields entry with `catalog.association_types`, route `admin.catalog.association_types.index`, an icon, and a sort value placing it near category fields.

- [ ] **Step 8: Run test — passes.** Run: `vendor/bin/pest --filter=AssociationTypeControllerTest`.

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Admin/src/Http/Controllers/Catalog/AssociationTypeController.php packages/Webkul/Admin/src/Http/Requests/AssociationTypeRequest.php packages/Webkul/Admin/src/Routes/catalog-routes.php packages/Webkul/Admin/src/Config/acl.php packages/Webkul/Admin/src/Config/menu.php packages/Webkul/Admin/tests/Feature/Catalog/AssociationTypeControllerTest.php
git commit -m "feat(admin): association type controller, routes, acl, menu"
```

---

### Task 8: Reusable field-builder component + admin blades (index / create / edit)

**Component-first:** the field repeater is built ONCE as a reusable Blade component and consumed by both create and edit — no duplicated repeater markup, no raw form controls.

**Files:**
- Create (reusable component): `packages/Webkul/Admin/src/Resources/views/components/associations/field-builder.blade.php` — the whole custom-field repeater (add/remove field, type dropdown, validation dropdown, required/unique/value_per_locale toggles, options sub-repeater with per-locale labels). Anonymous component consumed as `<x-admin::associations.field-builder :fields="..." />`.
- Create: `packages/Webkul/Admin/src/Resources/views/catalog/associations/types/index.blade.php`
- Create: `packages/Webkul/Admin/src/Resources/views/catalog/associations/types/create.blade.php`
- Create: `packages/Webkul/Admin/src/Resources/views/catalog/associations/types/edit.blade.php`

**Interfaces:**
- Consumes: controller view data (`$associationType` on edit; DataGrid rendered in index).
- Produces: index renders `AssociationTypeDataGrid`; create/edit render a form (all controls via `x-admin::form.control-group[.label|.control|.error]`) posting to the Task-7 routes with `code`, per-locale `name`, `status`, `position`, and the `<x-admin::associations.field-builder>` component emitting `fields[<index>][...]` + `fields[<index>][options][...]` names the controller's `fields.*` rules + repo consume.

- [ ] **Step 1: Write index blade** using UnoPim components only:

```blade
<x-admin::layouts>
    <x-slot:title>@lang('admin::app.catalog.association-types.index.title')</x-slot:title>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.catalog.association-types.index.title')
        </p>

        @if (bouncer()->hasPermission('catalog.association_types.create'))
            <a href="{{ route('admin.catalog.association_types.create') }}" class="primary-button">
                @lang('admin::app.catalog.association-types.index.create-btn')
            </a>
        @endif
    </div>

    <x-admin::datagrid :src="route('admin.catalog.association_types.index')" />
</x-admin::layouts>
```

- [ ] **Step 2: Build the reusable `field-builder` component** — extract the repeater from `packages/Webkul/Admin/src/Resources/views/catalog/categories/field/create.blade.php` (type/validation dropdowns from `config('association_field_types')`, drag-sortable options sub-repeater, per-locale option labels) into `components/associations/field-builder.blade.php`. Every control uses `x-admin::form.control-group[.label|.control|.error]` — convert any leftover raw `<select>/<input>` from the source to components. Emit `fields[<index>][...]` / `fields[<index>][options][...]` names. Accept a `:fields` prop to prefill on edit.

- [ ] **Step 3: Write create + edit blades** — thin wrappers: an outer `<x-admin::form>` with `code`, per-locale `name`, `status`, `position` controls (all `x-admin::form.control-group`), then `<x-admin::associations.field-builder :fields="old('fields') ?? ($associationType->fields ?? [])" />`, then submit. No repeater markup duplicated between create and edit.

- [ ] **Step 4: Route render test** — extend `AssociationTypeControllerTest`: `GET admin.catalog.association_types.create` returns 200 and the field-builder component renders (assert the type-dropdown label / a field-type option present); after creating a type with a `quantity` field, the edit page shows it. Run: `vendor/bin/pest --filter=AssociationTypeControllerTest`.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/components/associations packages/Webkul/Admin/src/Resources/views/catalog/associations
git commit -m "feat(admin): reusable association field-builder component + type blades"
```

---

### Task 9: Translations (all 33 locales)

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en_US/app.php` — add `catalog.association-types.*` keys (index title, create/edit titles, field labels: code, name, type, validation, required, unique, value-per-locale, section, options, add-option; buttons; success/error messages; delete-default-error).
- Modify: the same block in all other 32 locale `app.php` files with natural translations.
- Modify: `packages/Webkul/Product/src/Resources/lang/*/app.php` if any Product-package strings were added (e.g. default type display names — optional, defaults are seeded in DB).

**Interfaces:** none (strings only).

- [ ] **Step 1: Add the `en_US` keys** — enumerate every `@lang('admin::app.catalog.association-types...')` key used in Tasks 6-8; add them under a new `association-types` node inside `catalog`.

- [ ] **Step 2: Propagate to all 32 other locales** — translate values naturally (not English copies); keep `:param` placeholders intact. Locales: ar_AE ca_ES da_DK de_DE en_AU en_GB en_NZ es_ES es_VE fi_FI fr_FR hi_IN hr_HR id_ID it_IT ja_JP ko_KR mn_MN nl_NL no_NO pl_PL pt_BR pt_PT ro_RO ru_RU sv_SE tl_PH tr_TR uk_UA vi_VN zh_CN zh_TW.

- [ ] **Step 3: Verify**

Run: `php artisan unopim:translations:check`
Expected: zero missing keys / zero errors.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/lang packages/Webkul/Product/src/Resources/lang
git commit -m "i18n: association type admin strings for all 33 locales"
```

---

### Task 10: Playwright E2E — types + field builder

**Files:**
- Create: `tests/e2e-pw/tests/catalog/association-types.spec.js`

**Interfaces:** drives the running admin app (base URL `http://127.0.0.1:8000`, saved auth state).

- [ ] **Step 1: Write the spec** — (a) navigate to Association Types, assert the 3 seeded defaults show and have no delete control; (b) create a custom type `bundle_kit` with name "Bundle / Kit" and a required `quantity` field (type text, validation number); (c) reload the edit page and assert `quantity` persists; (d) delete the custom type succeeds.

- [ ] **Step 2: Run**

Run: `cd tests/e2e-pw && npx playwright test tests/catalog/association-types.spec.js`
Expected: all pass. (Ensure `php artisan serve` + `npm run dev`/built assets are up.)

- [ ] **Step 3: Commit**

```bash
git add tests/e2e-pw/tests/catalog/association-types.spec.js
git commit -m "test(e2e): association type creation + field builder"
```

---

## Self-Review

**Spec coverage (Plan 1 slice):**
- Configurable types via Admin UI CRUD → Tasks 6-8. ✓
- Custom fields per type, Category-field style → Tasks 1-4, 8. ✓
- Defaults seeded, non-user-defined, undeletable → Tasks 5, 6, 7. ✓
- Field types/rules mirror Category → Task 3. ✓
- Translations all 33 locales → Task 9. ✓
- Deferred to later plans (correctly out of Plan 1 scope): `product_associations` link storage + backfill + compat (Plan 2), product edit `links.blade` + `AssociationValidator` (Plan 3), REST/DataTransfer/AI tool (Plan 4). The `getValidationUniqueField()` referencing `product_associations` is written now but only exercised in Plan 3.

**Placeholder scan:** No TBD/TODO. Large boilerplate (blades, controller, DataGrid) uses explicit "copy source X, apply substitution S, plus these deltas" — legitimate in a clone-heavy existing codebase; substitution table + deltas are concrete.

**Type consistency:** Repo methods named consistently (`getActiveTypes`, `getActiveFields`, `getActiveFieldsBySection`). Route names `admin.catalog.association_types.*` used identically in Tasks 7-8. Config key `association_field_types` consistent across Task 3 rule + config + provider.

**Assumptions to verify during execution (flagged, not blocking):**
- Exact Concord module provider filename/array shape in Product package (Task 2 Step 9) — read the Category `ModuleServiceProvider` to match.
- `TranslatableModel` create-with-translations convention (Task 4) — match `CategoryRepository`/`CategoryFieldRepository` actual shape; adjust test payload accordingly.
- Product package's `ProductServiceProvider` is the right place for `mergeConfigFrom` (Task 3) — confirm it exists / is the config-registering provider.
