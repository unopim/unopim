# Configurable Associations — Plan 2: Links Storage & Dual-Write

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the `product_associations` link table (with per-link `additional_data`), a repository, a one-off backfill of existing JSON associations, and dual-write wiring so every product save writes links to BOTH the table and the legacy `values['associations']` JSON — keeping all existing readers working unchanged.

**Architecture:** The new table is a parallel, authoritative store for association LINKS (source product → related product, under a type, with custom-field values). During the Plan 2-4 transition we DUAL-WRITE: existing code keeps updating `values['associations'][type]` SKU lists (so normalizer/presenter/REST/import-export read exactly as before), and in the same save path we sync the corresponding rows into `product_associations`. A backfill migration seeds the table from existing product JSON. Rich `additional_data` and custom (non-legacy) association types are written through the new repository by Plan 3's UI; Plan 2 provides that repository and wires the 3 legacy types.

**Tech Stack:** Laravel 13, Concord, Pest 3, MySQL.

## Global Constraints

- PHP 8.3+, Laravel 13. Repository pattern (no Eloquent in controllers/types — go through the repository). Concord PROXY models only — relations use `...Proxy::modelClass()`, never concrete classes.
- Migrations in `packages/Webkul/Product/src/Database/Migrations/`. Tables declared WITHOUT prefix (Laravel adds it). Every custom index/FK/unique identifier gets an EXPLICIT name **< 64 chars** (DB_PREFIX-safe) — this bit us in Plan 1.
- Performance: index every FK + the `(product_id, association_type_id)` lookup. Backfill MUST chunk (`->chunkById(...)`) — never load all products at once. No per-row queries inside loops where a batch/keyed-map works; preload a `sku → id` map and a `type code → id` map once.
- Security: FK constraints with `onDelete('cascade')` from links to products AND to association_types (a deleted product/type removes its links). Backfill skips SKUs that don't resolve and self-referential links (product linking to itself), mirroring the existing importer (`prepareOtherSections()`).
- Back-compat (non-negotiable): the legacy `values['associations'][type] = [sku,...]` JSON MUST keep being written exactly as today for the 3 default types. Dual-write ADDS table rows; it never removes or changes the JSON behavior. Existing reader code is NOT modified in Plan 2.
- Extendability: fire `product_association.sync.before` / `.after` events around the dual-write sync. Reuse the `AssociationTypeRepository` (Plan 1) for type-code→id resolution — do not hardcode the 3 type ids.
- After EVERY php change: `vendor/bin/pint` then `vendor/bin/pint --test` (zero issues). Pest tests pass before commit.

## Current-state reference (verified — read before implementing)

- Write path (admin): `packages/Webkul/Product/src/Type/AbstractType.php::update()` lines 137-147 copy `up_sells`/`cross_sells`/`related_products` from `$data` into `$productValues['associations'][key]`, then `$product->values = $productValues; $product->save()` (line 153-159). There is a matching create path in the same file (`create()` / `prepareProductValues`) — find where associations first land on create and wire there too, OR wire in a shared `save()`-adjacent hook. **Confirm both create and update paths during implementation.**
- Write path (programmatic/API): `packages/Webkul/Product/src/ValueSetter.php` `setUpSellsAssociation()` (L53), `setCrossSellsAssociation()` (L63), `setRelatedAssociation()` (L73) each set `$this->values['associations'][key] = $data`.
- Constants: `AbstractType::ASSOCIATION_VALUES_KEY='associations'`, `RELATED_ASSOCIATION_KEY='related_products'`, `UP_SELLS_ASSOCIATION_KEY='up_sells'`, `CROSS_SELLS_ASSOCIATION_KEY='cross_sells'`, `ASSOCIATION_SECTIONS=[related_products, up_sells, cross_sells]`.
- Readers (DO NOT MODIFY in Plan 2 — they must keep working off JSON): `ProductValueMapper::getAssociations()` (reads `values.associations[type]`), `ProductAttributeValuesNormalizer::normalizeAssociations()`, `Presenters/ProductValuesPresenter`, importer `prepareOtherSections()` (Importer.php L1618-1668, the sku-resolution + self-skip logic to mirror in backfill).
- Plan 1 gives you: `association_types` table (+ seeded `related_products`/`up_sells`/`cross_sells`), `AssociationType` model + `AssociationTypeRepository` (has `getActiveTypes()`; add a code→id helper if missing).

---

### Task 1: `product_associations` migration

**Files:**
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_11_100001_create_product_associations_table.php`
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationSchemaTest.php`

**Interfaces:**
- Produces table `product_associations(id, product_id, association_type_id, related_product_id, position, additional_data JSON, timestamps)`, FKs to `products`(product_id, related_product_id) and `association_types`(association_type_id) all `onDelete('cascade')`, unique `(product_id, association_type_id, related_product_id)`, index `(product_id, association_type_id)`.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_associations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->integer('association_type_id')->unsigned();
            $table->integer('related_product_id')->unsigned();
            $table->integer('position')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'association_type_id', 'related_product_id'],
                'product_assoc_unique_link'
            );
            $table->index(['product_id', 'association_type_id'], 'product_assoc_product_type_index');

            $table->foreign('product_id', 'product_assoc_product_fk')
                ->references('id')->on('products')->onDelete('cascade');
            $table->foreign('related_product_id', 'product_assoc_related_fk')
                ->references('id')->on('products')->onDelete('cascade');
            $table->foreign('association_type_id', 'product_assoc_type_fk')
                ->references('id')->on('association_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_associations');
    }
};
```

- [ ] **Step 2: Write schema test** `ProductAssociationSchemaTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('creates the product_associations table with its columns', function () {
    expect(Schema::hasTable('product_associations'))->toBeTrue()
        ->and(Schema::hasColumns('product_associations', [
            'id', 'product_id', 'association_type_id', 'related_product_id', 'position', 'additional_data',
        ]))->toBeTrue();
});
```

- [ ] **Step 3: Migrate + run test.** `php artisan migrate` then `vendor/bin/pest --filter=ProductAssociationSchemaTest` → PASS. (Every explicit identifier above is < 64 chars — verify.)

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Database/Migrations/2026_07_11_100001_create_product_associations_table.php packages/Webkul/Product/tests/Feature/ProductAssociationSchemaTest.php
git commit -m "feat(product): product_associations link table"
```

---

### Task 2: ProductAssociation model + proxy + contract

**Files:**
- Create: `packages/Webkul/Product/src/Contracts/ProductAssociation.php`
- Create: `packages/Webkul/Product/src/Models/ProductAssociation.php`, `ProductAssociationProxy.php`
- Modify: the Product package Concord `ModuleServiceProvider` `$models` array (same place Plan 1 registered its models)
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationModelTest.php`

**Interfaces:**
- Produces `ProductAssociation` model: `$fillable = ['product_id','association_type_id','related_product_id','position','additional_data']`, `$casts = ['additional_data' => 'array']`, relations `product(): BelongsTo` → `ProductProxy::modelClass()`, `relatedProduct(): BelongsTo` → `ProductProxy::modelClass()` (foreign key `related_product_id`), `associationType(): BelongsTo` → `AssociationTypeProxy::modelClass()`.

- [ ] **Step 1: Write failing test**

```php
<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\ProductAssociation;

it('stores a link with additional_data and resolves its relations', function () {
    $type = AssociationType::where('code', 'up_sells')->firstOrFail();

    // Two products from the standard product seeders/factories — adjust to the repo's product factory/helper.
    [$source, $target] = \Webkul\Product\Models\Product::query()->limit(2)->pluck('id');

    $link = ProductAssociation::create([
        'product_id'          => $source,
        'association_type_id'  => $type->id,
        'related_product_id'   => $target,
        'position'             => 1,
        'additional_data'      => ['common' => ['quantity' => '2']],
    ]);

    expect($link->fresh()->additional_data)->toBe(['common' => ['quantity' => '2']])
        ->and($link->relatedProduct->id)->toBe($target)
        ->and($link->associationType->code)->toBe('up_sells');
});
```

*(If no two products exist in the test DB, use the repo's existing product factory/helper — read an existing Product test to see how products are created — and adjust. Do not invent a factory.)*

- [ ] **Step 2: Run — fails** (`vendor/bin/pest --filter=ProductAssociationModelTest`, class not found).

- [ ] **Step 3: Write contract** `Contracts/ProductAssociation.php`:

```php
<?php

namespace Webkul\Product\Contracts;

interface ProductAssociation {}
```

- [ ] **Step 4: Write model** `Models/ProductAssociation.php`:

```php
<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Contracts\ProductAssociation as ProductAssociationContract;

class ProductAssociation extends Model implements ProductAssociationContract
{
    protected $fillable = [
        'product_id',
        'association_type_id',
        'related_product_id',
        'position',
        'additional_data',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'related_product_id');
    }

    public function associationType(): BelongsTo
    {
        return $this->belongsTo(AssociationTypeProxy::modelClass());
    }
}
```

- [ ] **Step 5: Write proxy** `Models/ProductAssociationProxy.php` (mirror Plan 1 proxies):

```php
<?php

namespace Webkul\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductAssociationProxy extends ModelProxy {}
```

- [ ] **Step 6: Register in Concord** — add `ProductAssociation` to the Product `ModuleServiceProvider` `$models` array (same pattern Plan 1 used).

- [ ] **Step 7: Run — passes.** `vendor/bin/pest --filter=ProductAssociationModelTest`.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Contracts/ProductAssociation.php packages/Webkul/Product/src/Models/ProductAssociation*.php packages/Webkul/Product/src/Providers packages/Webkul/Product/tests/Feature/ProductAssociationModelTest.php
git commit -m "feat(product): product association link model"
```

---

### Task 3: ProductAssociationRepository + sync service

**Files:**
- Create: `packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php`
- Add code→id helper on `AssociationTypeRepository` if absent (e.g. `findByCode(string $code): ?AssociationType`).
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationRepositoryTest.php`

**Interfaces:**
- Consumes: `ProductAssociation` model, `AssociationTypeRepository`, and a SKU→id resolver (reuse the product repository / `SkuStorage` the importer uses — read `Importer.php` to find it; do NOT invent).
- Produces:
  - `getLinksForProduct(int $productId): Collection` — all rows for a source product, eager-loaded `associationType` + `relatedProduct` (no N+1).
  - `syncType(int $productId, int $associationTypeId, array $links): void` — replaces all rows for `(productId, associationTypeId)` with the given `$links` (each: `['related_product_id'=>int, 'position'=>?int, 'additional_data'=>?array]`). Deletes rows no longer present, upserts the rest. Wrapped in a DB transaction. Fires `product_association.sync.before`/`.after`.
  - `syncFromSkuList(int $productId, string $typeCode, array $skus): void` — convenience used by the legacy dual-write: resolves `$typeCode`→type id and each SKU→product id (skipping unresolved + self), then calls `syncType` with empty `additional_data`. This is what Task 4 calls.

- [ ] **Step 1: Write failing test** — create a source product + 2 targets; call `syncFromSkuList($source, 'up_sells', [$targetSkuA, $targetSkuB, $sourceSku, 'NONEXISTENT'])`; assert exactly 2 rows exist for `(source, up_sells)` (self + nonexistent skipped), relations resolve, and a second call with `[$targetSkuA]` prunes down to 1 row. Model product creation on an existing Product test.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement the repository** per the Interfaces above. `syncType` in a `DB::transaction`: load existing rows keyed by `related_product_id`; delete those not in the new set; update changed `position`/`additional_data`; insert new ones. `syncFromSkuList` resolves ids via the same SKU→id mechanism the importer uses and skips unresolved/self. Add `AssociationTypeRepository::findByCode()` if it doesn't exist.

- [ ] **Step 4: Run — passes.**

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php packages/Webkul/Product/src/Repositories/AssociationTypeRepository.php packages/Webkul/Product/tests/Feature/ProductAssociationRepositoryTest.php
git commit -m "feat(product): product association repository + sync service"
```

---

### Task 4: Dual-write wiring in the product save path

**Files:**
- Modify: `packages/Webkul/Product/src/Type/AbstractType.php` (`update()` around L137-153, and the create path — confirm where associations land on create)
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationDualWriteTest.php`

**Interfaces:**
- Consumes: `ProductAssociationRepository::syncFromSkuList`.
- Produces: after a product is saved with association data, for EACH of `AbstractType::ASSOCIATION_SECTIONS`, the JSON `values['associations'][section]` is written EXACTLY as today (unchanged lines) AND `syncFromSkuList($product->id, $section, $skuList)` is called so `product_associations` mirrors it. When a section is absent/empty in `$data`, leave the JSON path as-is; the table sync for that section should reflect the resulting JSON state (i.e. sync to whatever the product ends up with, so removals propagate). Resolve the repository via `app(ProductAssociationRepository::class)` (or constructor injection if the type is container-resolved).

- [ ] **Step 1: Write failing test** — via the product repository/type, create/update a product supplying `up_sells => [skuA, skuB]` and `related_products => [skuC]`; assert (a) `product->values['associations']['up_sells']` still equals `[skuA,skuB]` (JSON unchanged — back-compat), AND (b) `product_associations` has 2 up_sells rows + 1 related row for that product. Then update the product changing `up_sells => [skuA]`; assert JSON now `[skuA]` and the table pruned to 1 up_sells row. Model product create/update on an existing Product feature test.

- [ ] **Step 2: Run — fails** (table not synced).

- [ ] **Step 3: Wire dual-write** — in `AbstractType::update()` keep lines 137-147 verbatim; after `$product->save()` (and only when the product has an id), iterate `ASSOCIATION_SECTIONS` and call `syncFromSkuList($product->id, $section, $productValues['associations'][$section] ?? [])`. Do the same at the create path where associations are first persisted. Guard so a product with NO association data still works (syncing empty lists is a no-op prune). Keep it resilient: wrap the sync so a sync failure does not abort the product save (log + rethrow only in non-prod? — follow the repo's existing error convention; at minimum do not silently swallow).

- [ ] **Step 4: Run — passes.** Also run the broader Product suite to confirm no regression in existing association behavior.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Type/AbstractType.php packages/Webkul/Product/tests/Feature/ProductAssociationDualWriteTest.php
git commit -m "feat(product): dual-write associations to link table on save"
```

---

### Task 5: Backfill migration (JSON → link rows)

**Files:**
- Create: `packages/Webkul/Product/src/Database/Migrations/2026_07_11_100002_backfill_product_associations.php`
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationBackfillTest.php`

**Interfaces:**
- Consumes: existing `products.values['associations']` JSON; `association_types` (code→id); products (sku→id).
- Produces: one `product_associations` row per legacy JSON link, `additional_data = null`. Idempotent (`updateOrInsert` on the unique triple, or delete-then-insert per product). Skips unresolved SKUs + self-links. Chunked.

- [ ] **Step 1: Write failing test** — seed a product whose `values['associations']` contains `up_sells => [existingSku, 'GHOST']` and `related_products => [selfSku]`; run the backfill (call the migration's `up()` or `php artisan migrate`); assert exactly 1 up_sells row (GHOST + self skipped) and 0 related rows; assert a second run creates no duplicates.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Write the backfill** — build `code→typeId` map and stream products with `->chunkById(200)`; for each product read `values['associations']`, resolve each section's SKUs against a preloaded `sku→id` map (skip missing + self), and `updateOrInsert` rows keyed by `(product_id, association_type_id, related_product_id)`. Preload maps ONCE outside the chunk loop. `down()` may be a no-op (data migration) or delete rows with `additional_data IS NULL` created by backfill — choose no-op and document why (safer; dual-write keeps the table current regardless).

- [ ] **Step 4: Migrate + run test — passes.** Confirm idempotency (run twice, row count stable).

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Product/src/Database/Migrations/2026_07_11_100002_backfill_product_associations.php packages/Webkul/Product/tests/Feature/ProductAssociationBackfillTest.php
git commit -m "feat(product): backfill product associations from legacy json"
```

---

### Task 6: ValueSetter parity (programmatic/API write path)

**Files:**
- Modify: `packages/Webkul/Product/src/ValueSetter.php` (the 3 association setters) OR document that the API save funnels through `AbstractType::update` (Task 4 already covers it)
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationValueSetterTest.php`

**Interfaces:**
- Ensures the programmatic/API path (`ValueSetter::set*Association`) that writes `values['associations']` ALSO results in link-table rows. FIRST verify whether the API create/update ultimately calls `AbstractType::update()` (Task 4) — if it does, the table is already synced and this task only ADDS a test proving the API path produces table rows (no code change). If the API path bypasses `AbstractType::update()`, add the sync there too.

- [ ] **Step 1: Trace the API save path** — read `packages/Webkul/AdminApi/src/Http/Controllers/API/Catalog/ProductController.php` create/update to confirm whether it calls the product repository/type `update()` (Task 4's synced path) or persists `values` directly. Record the finding.

- [ ] **Step 2: Write a test** exercising the API/programmatic path (ValueSetter → save) and asserting `product_associations` rows appear. If it fails because that path bypasses Task 4, wire `syncFromSkuList` there (mirroring Task 4) and make it pass. If it already passes, keep the test as a regression guard and note no code change was needed.

- [ ] **Step 3: Run — passes.**

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/ValueSetter.php packages/Webkul/Product/tests/Feature/ProductAssociationValueSetterTest.php
git commit -m "feat(product): ensure api write path syncs association link table"
```

---

## Self-Review

**Spec coverage (Plan 2 slice of the design doc):**
- Dedicated `product_associations` table with `additional_data` → Task 1-2. ✓
- Repository + sync service → Task 3. ✓
- Dual-write (JSON stays authoritative for reads; table mirrored) → Tasks 4, 6. ✓
- Backfill existing JSON → table, idempotent, sku/self-skip → Task 5. ✓
- Back-compat: no reader modified, JSON write path unchanged → Tasks 4/6 keep lines verbatim, add sync alongside. ✓
- Deferred (correctly out of Plan 2): product edit UI for custom types + `additional_data` entry (Plan 3); moving readers off JSON + REST/import-export richer shape + JSON retirement (Plan 4).

**Placeholder scan:** No TBD/TODO. Product-factory / SKU-resolver specifics are intentionally "read the existing Product test / importer and match" because the repo's helpers are the source of truth — each such spot names the exact file to read and forbids inventing a new mechanism.

**Type consistency:** `syncType`/`syncFromSkuList`/`getLinksForProduct` names used consistently across Tasks 3-6. `additional_data` cast + shape (`['common'=>[...]]`) consistent with Plan 1's field-value convention. Unique triple `(product_id, association_type_id, related_product_id)` referenced identically in Tasks 1, 3, 5.

**Assumptions to verify during execution (flagged, non-blocking):**
- Exact create-path location where associations first persist in `AbstractType` (Task 4) — confirm both create and update wire the sync.
- The SKU→id resolver the importer uses (`SkuStorage`?) — reuse it in Tasks 3/5 (Task 3 Step 3 / Task 5 Step 3).
- Whether the AdminApi product save funnels through `AbstractType::update()` (Task 6 Step 1) — determines if Task 6 is code or test-only.
