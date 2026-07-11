# Configurable Associations — Plan 4: Import/Export, REST & AI

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Full-surface support for configurable associations beyond the admin UI: a dedicated row-per-link Association import/export job (custom types + per-link field values), a product-export "include associations" flag, close the product-importer link-table gap, rich REST support, and a dynamic AI tool.

**Architecture:** A new dedicated DataTransfer job (`product-associations`) handles rich per-link data as **one row per link** (`sku, association_type, related_sku` + a column per custom field). Import uses per-link **upsert/delete** by the unique triple `(product_id, association_type_id, related_product_id)` — NOT the UI's whole-type replace — so a batched file accumulates links. Legacy product-CSV association columns stay (membership only) and now also sync to the link table. REST and the AI tool gain custom-type + `additional_data` support via the existing `AbstractType::prepareRichAssociations()`/`syncRichAssociations()`.

**Tech Stack:** Laravel 13, Concord, DataTransfer (queued batch jobs), Pest 3.

## Global Constraints

- PHP 8.3+, Laravel 13. Repository pattern; Concord proxies. Reuse the existing link infra (Plans 1-3): `ProductAssociationRepository` (`syncFromSkuList` preserve, `syncTypeWithData` replace, shared `syncLinks`), `AssociationTypeRepository`, `AssociationTypeField`, `AssociationValidator`, `AbstractType::{prepareRichAssociations,syncRichAssociations,syncAssociationLinks}`. Do NOT rebuild any of it.
- **Import semantics (critical):** the association import is row-per-link with `append`/`delete` modes (like other UnoPim importers). `append` = upsert ONE link by the unique triple (set its `additional_data`), leaving other links of that type intact. `delete` = remove that one link. Do NOT call `syncTypeWithData`/`syncFromSkuList` per row (both REPLACE a whole type → would prune the file's earlier rows). Add a small repo method for single-link upsert/delete instead.
- **Back-compat:** legacy product-CSV columns (`up_sells`/`cross_sells`/`related_products` SKU lists) keep working unchanged. The product export's association columns become opt-in via a filter (default off). Legacy `values['associations']` JSON keeps being written; no reader changes.
- Follow the DataTransfer job pattern exactly (clone the CategoryField import/export trio — simplest analog). Job config in `Config/importers.php`/`exporters.php`; classes under `Helpers/Importers/ProductAssociation/`, `Helpers/Exporters/ProductAssociation/`, validators under `Validators/JobInstances/{Import,Export}/`.
- Translations: all new job titles/labels/messages in en_US + all 33 locales (natural), `unopim:translations:check` green. Provide a sample CSV.
- Security: import validates every row via `AssociationValidator` (field values) + SKU/type existence; ACL on REST + AI tool (`catalog.products.edit`). No SQLi (query builder bindings).
- After EVERY php change: `vendor/bin/pint` then `vendor/bin/pint --test` (zero issues). Pest passes before commit.

## Reference (verified map — read before implementing)

- Job registration: `packages/Webkul/DataTransfer/src/Config/importers.php` (`category-fields` L28-34, `products` L4-10), `exporters.php` (`category-fields` L265-294, `products` L4-227 with `filters.fields`). Merged in `Providers/DataTransferServiceProvider.php:31-32`.
- Import base: `Helpers/Importers/AbstractImporter.php` — implement `validateRow()`, `importBatch()`; declare `$validColumnNames`, `$permanentAttributes`, `$messages`. Template: `Helpers/Importers/CategoryField/{Importer,Storage}.php`. SKU map shape: `Helpers/Importers/Product/SKUStorage.php` (stores `['id','type','attribute_family_id']`).
- Export base: `Helpers/Exporters/AbstractExporter.php` — implement `exportBatch()`; `$this->source` = the `source` repo. Template: `Helpers/Exporters/CategoryField/Exporter.php`. Dynamic columns: `Helpers/Exporters/Product/Exporter.php::{setAttributesValues,getHeaderLabels}` (L537-675) + `getAssociations()` (L768-780).
- Validators: `Validators/JobInstances/Default/JobValidator.php`; templates `Import/CategoryFieldJobValidator.php`, `Export/CategoryFieldJobValidator.php`; rich export validator `Export/ProductJobValidator.php` (scope filters).
- Product importer gap: `Helpers/Importers/Product/Importer.php` — `prepareOtherSections()` L1618-1668 writes only JSON; `saveProducts()` L1394-1409 → `bulkInsertProducts()` (`DB::table('products')->insert` L1489, ids re-selected into skuStorage L1496-1512) / `bulkUpdateProducts()` (`upsert` L1444). No `syncAssociationLinks` call anywhere in the importer.
- REST: `AdminApi/.../ProductController.php` `updateProduct()` L43-112 (ValueSetter setUpSells/CrossSells/Related L71-81; `syncAssociationLinks` L104), `patchProduct()` L156-190 (sync L182). Rich entry point already exists: `AbstractType::prepareRichAssociations()` L242-323 / `syncRichAssociations()` L338-353 (parses `associations[<typeCode>][]={sku,additional_data}`, validates, writes per-link).
- AI tool: `AiAgent/src/Chat/Tools/ManageAssociations.php` `schema()` L31-40 (hardcoded sku/related/up_sells/cross_sells), `handle()` L42-131 (writes only JSON, no link-table sync).

---

### Task 1: Single-link upsert/delete on the repository

**Files:**
- Modify: `packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php`
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationUpsertLinkTest.php`

**Interfaces:**
- `upsertLink(int $productId, int $associationTypeId, int $relatedProductId, ?int $position, ?array $additionalData): void` — insert-or-update ONE row by `(product_id, association_type_id, related_product_id)`, setting `additional_data` (and position). Does NOT touch other links. Fires `product_association.sync.before/after`? No — keep it a low-level single-row op (events are for whole-type sync). Idempotent.
- `deleteLink(int $productId, int $associationTypeId, int $relatedProductId): void` — delete that one row if present.

- [ ] **Step 1: Write failing test** — upsert a link with `additional_data=['common'=>['quantity'=>'2']]`; assert row exists with that data; upsert again with quantity 5 → same row updated (no dup); upsert a SECOND related product under the same type → both rows exist (no prune of the first); `deleteLink` removes only the targeted one.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — `upsertLink` via `updateOrCreate`/`upsert` on the unique triple (Eloquent `updateOrCreate` for clean `additional_data` cast handling). `deleteLink` via `where(...)->delete()`. No whole-type prune.

- [ ] **Step 4: Run — passes.** Run the Plan 1-3 ProductAssociation tests to confirm no regression.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php packages/Webkul/Product/tests/Feature/ProductAssociationUpsertLinkTest.php
git commit -m "feat(product): single-link upsert/delete for association import"
```

---

### Task 2: Product importer → sync legacy sections to the link table (close Plan-2 gap)

**Files:**
- Modify: `packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php`
- Test: `packages/Webkul/DataTransfer/tests/Feature/ProductImportAssociationSyncTest.php` (follow existing DataTransfer test conventions)

**Interfaces:**
- After the product bulk write in `saveProducts()` (ids known — `bulkInsertProducts` collects ids into skuStorage L1496-1512; `bulkUpdateProducts` has the ids too), for each imported product with association JSON, sync the 3 legacy sections to `product_associations` via `ProductAssociationRepository::syncFromSkuList($productId, $section, $skus)` (preserve additional_data). This makes CSV product import populate the link table like admin/API/copy already do. Keep it resilient (report, don't abort the batch).

- [ ] **Step 1: Write failing test** — import a product CSV row with `up_sells = "SKU-A,SKU-B"` (SKU-A/B pre-existing); after import, assert `product_associations` has 2 up_sells rows for that product (currently 0 — only JSON is written). Model on an existing DataTransfer product import test.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — in `saveProducts()` (the single choke point), after both bulk paths, collect `sku→id` (from skuStorage) and for each product in the batch that has `values['associations']`, call `syncFromSkuList` per section. Resolve the type-code list from `AbstractType::ASSOCIATION_SECTIONS`. Batch-friendly: don't re-query per row; reuse skuStorage. Resilient try/catch + report.

- [ ] **Step 4: Run — passes.** Run the DataTransfer product import suite — no regression.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php packages/Webkul/DataTransfer/tests/Feature/ProductImportAssociationSyncTest.php
git commit -m "feat(datatransfer): sync product import associations to link table"
```

---

### Task 3: Dedicated Association IMPORT job (row-per-link + custom fields)

**Files:**
- Create: `packages/Webkul/DataTransfer/src/Helpers/Importers/ProductAssociation/{Importer.php,Storage.php}`
- Create: `packages/Webkul/DataTransfer/src/Validators/JobInstances/Import/ProductAssociationJobValidator.php`
- Modify: `packages/Webkul/DataTransfer/src/Config/importers.php` (+ lang + sample CSV)
- Test: `packages/Webkul/DataTransfer/tests/Feature/ProductAssociationImportTest.php`

**Interfaces:**
- Columns: `sku` (source), `association_type` (code), `related_sku` (target), + one column per custom field code (dynamic, from `association_type_fields`). `$permanentAttributes = ['sku','association_type','related_sku']`. `$validColumnNames` = those 3 + all active field codes.
- `validateRow()`: sku/related_sku exist (via Storage), self-link rejected, `association_type` exists, and the row's field columns validated via `AssociationValidator` for that type (build `additional_data` from the field columns using each field's `value_per_locale`/`getJsonPath` bucket — for import, treat field columns as `common` unless a locale-qualified column convention is defined; keep `common` for Plan 4, document locale-specific columns as a later extension).
- `importBatch()`: `append` mode → `ProductAssociationRepository::upsertLink($productId, $typeId, $relatedId, null, $additionalData)` (Task 1); `delete` mode → `deleteLink(...)`. Resolve ids via Storage (SKUStorage-style map for products + a code→id map for types).

- [ ] **Step 1: Write failing test** — a CSV with rows `(sku=P1, association_type=bundle_kit, related_sku=P2, quantity=2)` and `(P1, bundle_kit, P3, quantity=3)` imports → `product_associations` has 2 bundle_kit rows for P1 with quantities 2 and 3 (accumulated, not replaced); a `delete`-mode row removes one; an invalid quantity (non-numeric) is skipped/errored per validation strategy. Model on an existing DataTransfer import test (job track + batch flow).

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — clone `CategoryField/Importer.php` + `Storage.php`; make `$validColumnNames` dynamic (base 3 + `AssociationTypeField` codes); build `additional_data` from field columns; persist via Task-1 upsert/delete. Storage: product `sku→id` map + type `code→id` map.

- [ ] **Step 4: Register + sample + lang** — add the `product-associations` entry to `Config/importers.php` (title, importer, sample_path `data-transfer/samples/product-associations.csv`, validator, `has_file_options=true`); create the sample CSV; add `data_transfer::app.importers.product-associations.*` lang keys (all 33 locales).

- [ ] **Step 5: Run — passes;** `php artisan unopim:translations:check` green.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/DataTransfer/src/Helpers/Importers/ProductAssociation packages/Webkul/DataTransfer/src/Validators/JobInstances/Import/ProductAssociationJobValidator.php packages/Webkul/DataTransfer/src/Config/importers.php packages/Webkul/DataTransfer/src/Resources/lang packages/Webkul/DataTransfer/tests/Feature/ProductAssociationImportTest.php
git commit -m "feat(datatransfer): dedicated product association import job"
```

---

### Task 4: Dedicated Association EXPORT job (row-per-link + custom fields)

**Files:**
- Create: `packages/Webkul/DataTransfer/src/Helpers/Exporters/ProductAssociation/Exporter.php`
- Create: `packages/Webkul/DataTransfer/src/Validators/JobInstances/Export/ProductAssociationJobValidator.php`
- Modify: `packages/Webkul/DataTransfer/src/Config/exporters.php` (+ lang)
- Test: `packages/Webkul/DataTransfer/tests/Feature/ProductAssociationExportTest.php`

**Interfaces:**
- `source` = `Webkul\Product\Repositories\ProductAssociationRepository`. Exports ONE row per link: `sku` (source product), `association_type` (code), `related_sku` (target), + a column per custom field code (dynamic header, union across types — sparse per row), value read from the link's `additional_data` (common bucket; locale-specific deferred like import).
- `getResults()` iterates `product_associations` joined to products (source sku + related sku) + association_types (code). Dynamic columns mirror `Product\Exporter::getHeaderLabels()`.

- [ ] **Step 1: Write failing test** — seed 2 links (bundle_kit with quantity) → export → assert the output rows contain `sku, association_type, related_sku, quantity` with correct values, one row per link. Model on the CategoryField export test.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — clone `CategoryField/Exporter.php`; add dynamic field columns; `getResults()` joins the link table. Register the `product-associations` exporter entry (title, exporter, source, sample_path, validator, `filters.fields` with `file_format`); add lang (all 33 locales).

- [ ] **Step 4: Run — passes;** translations check green.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/DataTransfer/src/Helpers/Exporters/ProductAssociation packages/Webkul/DataTransfer/src/Validators/JobInstances/Export/ProductAssociationJobValidator.php packages/Webkul/DataTransfer/src/Config/exporters.php packages/Webkul/DataTransfer/src/Resources/lang packages/Webkul/DataTransfer/tests/Feature/ProductAssociationExportTest.php
git commit -m "feat(datatransfer): dedicated product association export job"
```

---

### Task 5: Product export "include associations" filter

**Files:**
- Modify: `packages/Webkul/DataTransfer/src/Config/exporters.php` (`products` filters), `Helpers/Exporters/Product/Exporter.php`, `Export/ProductJobValidator.php` if needed
- Test: `packages/Webkul/DataTransfer/tests/Feature/ProductExportAssociationFlagTest.php`

**Interfaces:**
- Add a boolean filter `with_associations` (default `false`/off) to the product exporter's `filters.fields`. When OFF, the product export omits the `up_sells`/`cross_sells`/`related_products` columns entirely (clean product file). When ON, includes them (legacy SKU-list columns, current behavior). Read the flag via `getExportParameter()`.

- [ ] **Step 1: Write failing test** — export products with `with_associations=false` → output has NO association columns; with `with_associations=true` → association columns present with SKU lists. (Assert on header labels + row content.)

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — add the filter field to config (a boolean/select yes-no, following an existing boolean filter in the product filters e.g. `with_media`); in `Product\Exporter`, gate the association column writes (L418-420/450-452) + their header labels behind the flag. Default off.

- [ ] **Step 4: Run — passes.** Confirm existing product export tests still pass (they may assume association columns — update expectations to the new default-off, or set the flag on in those tests; note whichever).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/DataTransfer/src/Config/exporters.php packages/Webkul/DataTransfer/src/Helpers/Exporters/Product packages/Webkul/DataTransfer/tests/Feature/ProductExportAssociationFlagTest.php
git commit -m "feat(datatransfer): opt-in associations filter on product export"
```

---

### Task 6: Rich REST support

**Files:**
- Modify: `packages/Webkul/AdminApi/src/Http/Controllers/API/Catalog/ProductController.php` (updateProduct/patchProduct); the product API resource/data-source that serializes a product (for GET output)
- Test: `packages/Webkul/AdminApi/tests/Feature/Catalog/ApiRichAssociationTest.php`

**Interfaces:**
- Accept, in the product create/update payload, the rich `associations` map `{ <typeCode>: [ {sku, additional_data?} ] }` (same shape the admin UI + `AbstractType::prepareRichAssociations` consume) IN ADDITION to the legacy `values.associations.<section>` flat lists. When the rich `associations` key is present, feed it through the type's rich path (so custom types + `additional_data` persist + validate); when absent, keep the existing ValueSetter+`syncAssociationLinks` legacy path unchanged.
- GET product output: include the product's links (all types, incl. custom + `additional_data`) in the API response (a new `associations` block), without breaking the existing `values.associations` legacy output.

- [ ] **Step 1: Write failing test** — (a) `PUT`/`POST` a product with `associations => { bundle_kit: [{sku:X, additional_data:{common:{quantity:'2'}}}] }` → the bundle_kit link + quantity persist in `product_associations`; (b) legacy `values.associations.up_sells=[X]` payload still works; (c) GET the product → response includes the bundle_kit link with quantity. Model on existing AdminApi product tests.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — in `updateProduct()`/`patchProduct()`, detect the rich `associations` key and route it via the type instance's rich path (`prepareRichAssociations`/`syncRichAssociations` — expose a clean entry if needed, e.g. pass `associations` through `$data` into `AbstractType::update` which already handles it, OR call the rich sync explicitly after save). Add the `associations` block to the product GET serializer via `ProductAssociationRepository::getLinksForProduct()`. Keep legacy paths intact.

- [ ] **Step 4: Run — passes.** Run the AdminApi suite — no regression.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/AdminApi/src packages/Webkul/AdminApi/tests/Feature/Catalog/ApiRichAssociationTest.php
git commit -m "feat(api): rich association support in product REST endpoints"
```

---

### Task 7: Dynamic AI tool

**Files:**
- Modify: `packages/Webkul/AiAgent/src/Chat/Tools/ManageAssociations.php`
- Test: `packages/Webkul/AiAgent/tests/Feature/ManageAssociationsTest.php` (follow AiAgent test conventions; if none, a focused unit/feature test)

**Interfaces:**
- Replace the hardcoded `up_sells`/`cross_sells` schema params with a dynamic structure: accept `sku` (source) + a list of associations `[{association_type, related_sku, additional_data?}]` (or a per-type map), where `association_type` is any active type code. Validate types/SKUs + field values (`AssociationValidator`). After writing, call `$product->getTypeInstance()->syncAssociationLinks(...)` / the rich sync so the AI's changes hit `product_associations` (currently it only writes JSON). Keep `append`/`replace` modes. Keep the `catalog.products.edit` permission check.

- [ ] **Step 1: Write failing test** — invoke the tool to add a `bundle_kit` link with quantity to a product → assert `product_associations` has the row with quantity (currently the tool writes only JSON and doesn't support custom types). Also a legacy up_sells add still works.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — make `schema()` dynamic (enumerate active association types, or accept a generic `association_type` string validated in `handle()`); support `additional_data`; after `$product->save()` sync the link table via the type instance (mirror the AdminApi controller). Validate via `AssociationValidator`.

- [ ] **Step 4: Run — passes.**

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/AiAgent/src/Chat/Tools/ManageAssociations.php packages/Webkul/AiAgent/tests/Feature/ManageAssociationsTest.php
git commit -m "feat(ai): dynamic association types + fields in ManageAssociations tool"
```

---

## Self-Review

**Spec coverage:** dedicated row-per-link import (Task 3) + export (Task 4) with custom-field columns; product-export opt-in flag (Task 5); product-importer link-table sync closing the Plan-2 gap (Task 2); single-link upsert primitive for import accumulation (Task 1); rich REST (Task 6); dynamic AI tool (Task 7). Legacy JSON retirement is intentionally NOT in Plan 4 (dual-write + legacy readers remain; retiring JSON is a separate, riskier follow-up once every reader is migrated).

**Placeholder scan:** none — each task names the exact clone template + registration point from the verified map.

**Key semantic locks:** import = per-link upsert/delete (append/delete modes), NOT whole-type replace (Task 1 provides the primitive; Task 3 uses it). Export/import field columns are `common`-bucket only in Plan 4; locale-specific field columns documented as a later extension. Product-export association columns default OFF.

**Assumptions to verify during execution:** exact DataTransfer test harness for job-track/batch flow (Tasks 2-5) — model on an existing DataTransfer feature test; the product API serializer location for the GET `associations` block (Task 6); AiAgent tool test conventions (Task 7).
