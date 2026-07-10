# Configurable Associations — Plan 3: Product Edit UI & Rich Link Data

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** On the product edit "Links" tab, show ALL active association types (not the hardcoded 3), let the user add related products under any type AND fill that type's custom fields per link (e.g. quantity), persist those values into `product_associations.additional_data`, validate them, and keep the legacy JSON + dual-write fully working.

**Architecture:** The Vue `v-product-links` component becomes data-driven from `AssociationTypeRepository::getActiveTypes()` (each type carries its fields + existing links with `additional_data`). It emits a unified `associations[<typeCode>][<i>][sku]` + `associations[<typeCode>][<i>][additional_data][...]` payload. `AbstractType::update()` accepts BOTH this new shape and the legacy flat `up_sells[]` shape (REST/import back-compat), writes the legacy `values['associations']` JSON for the 3 legacy sections unchanged, and syncs ALL types into `product_associations` WITH `additional_data`. The dual-write sync is upgraded to PRESERVE/set `additional_data` (never force-null). An `AssociationValidator` (mirroring `CategoryValidator`) validates per-link field values from each type's field definitions.

**Tech Stack:** Laravel 13, Vue 3, Tailwind, Concord, Pest 3, Playwright.

## Global Constraints

- PHP 8.3+, Laravel 13. Component-first admin UI: NO raw visible form controls — reuse `x-admin::form.control-group.*` and the existing category `dynamic-fields` component (or a new reusable per-link component); build a reusable component and consume it, never inline duplicated markup.
- Repository pattern; Concord proxies. Reuse Plan 1's `AssociationTypeRepository`/`AssociationTypeField` field system and Plan 2's `ProductAssociationRepository`. Do NOT duplicate the field-value validation logic — mirror `CategoryValidator`/`FieldValidator`.
- **Back-compat (non-negotiable):** the legacy flat payload (`up_sells[]=sku`, etc. — still sent by REST/import/older forms) MUST keep working, and the legacy `values['associations'][section]` JSON MUST keep being written for the 3 default sections exactly as today. The new unified `associations[...]` payload is ADDITIVE; when present it supersedes the flat keys for the same type.
- **additional_data preservation (critical — from Plan 2 review):** the runtime dual-write must NOT null out `additional_data` on existing links. When syncing a link that already has custom-field values and the incoming sync doesn't specify new ones, PRESERVE the existing `additional_data`. When the UI DOES specify values, write them.
- Fields follow the Category-field value convention: `additional_data = { common: {...}, locale_specific: { <locale>: {...} } }`, `value_per_locale` decides the bucket (Plan 1 `AssociationTypeField::getJsonPath()`).
- Translations: any new UI string in en_US + all 33 locales (natural), `unopim:translations:check` green.
- After EVERY php change: `vendor/bin/pint` then `vendor/bin/pint --test` (zero issues). Pest passes before commit. Playwright for the E2E task.

## Current-state reference (read before implementing)

- UI: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php` — Vue `v-product-links`: `types` hardcoded (L159-176), `addedProducts` keyed by type of `normalizeWithImage()` product objects (L178-184), hidden input `type.key+'[]'`=sku (L59-63), product-search drawer (L140-145), `addSelected`/`remove` (L203-219).
- Include: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php` L246-249 passes `up_sells`/`cross_sells`/`related_products` arrays from `$product->values['associations']`.
- Save: `AbstractType::update()` L137-147 reads `$data[up_sells|cross_sells|related_products]`, writes `values['associations'][section]`; then Plan 2's `syncAssociationLinks()` mirrors to the table (currently `additional_data=null`).
- Field system (Plan 1): `AssociationTypeField` has `getValidationRules()`, `getJsonPath()`, `value_per_locale`, `options`; `AssociationTypeRepository::getActiveTypes()` eager-loads `translations`+`fields`. Category value validation to mirror: `packages/Webkul/Category/src/Validator/Catalog/CategoryValidator.php` + `FieldValidator.php`. Category per-value editor to mirror: `packages/Webkul/Admin/src/Resources/views/components/categories/dynamic-fields.blade.php`.
- Link store (Plan 2): `ProductAssociationRepository::syncFromSkuList()` (SKU list, additional_data=null), `syncType()` (rich links with additional_data), `getLinksForProduct()`.

---

### Task 1: Repository — preserve/set additional_data on sync

**Files:**
- Modify: `packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php`
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationAdditionalDataSyncTest.php`

**Interfaces:**
- `syncFromSkuList()` (legacy dual-write): change so that for a link that PERSISTS across the sync, existing `additional_data` is PRESERVED (not overwritten to null). Only membership (add/remove rows) and position change; `additional_data` for surviving rows is left intact.
- Add `syncTypeWithData(int $productId, int $associationTypeId, array $links): void` where each link is `['related_product_id'=>int, 'position'=>?int, 'additional_data'=>?array]` — this is the UI path; it SETS `additional_data` from the payload (null/absent means clear to null for that link, since the UI is authoritative for a type it submitted). Reuse the same transactional delete/prune/insert scoping as `syncType`. (If `syncType` already does exactly this, expose/rename it and just fix `syncFromSkuList`'s preservation — do not duplicate.)

- [ ] **Step 1: Write failing test** — (a) create a link with `additional_data=['common'=>['quantity'=>'5']]`; call `syncFromSkuList(product,'up_sells',[thatSku, otherSku])`; assert the first link STILL has `quantity=5` (preserved) and the new link exists. (b) call `syncTypeWithData` with explicit `additional_data` for a link; assert it's written. (c) `syncTypeWithData` removing a link prunes it.

- [ ] **Step 2: Run — fails** (current syncFromSkuList nulls additional_data on the diff-update path).

- [ ] **Step 3: Implement** — in `syncFromSkuList`, when building the link set, do NOT include `additional_data` in the update for surviving rows (only insert new rows with null, prune removed, keep existing untouched). Add/confirm `syncTypeWithData` sets additional_data explicitly. Keep transactional scoping by `(productId, associationTypeId)`.

- [ ] **Step 4: Run — passes.** Also run the Plan-2 dual-write + backfill tests to confirm no regression.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Repositories/ProductAssociationRepository.php packages/Webkul/Product/tests/Feature/ProductAssociationAdditionalDataSyncTest.php
git commit -m "fix(product): preserve additional_data in legacy sync; add rich sync"
```

---

### Task 2: AssociationValidator (per-link field values)

**Files:**
- Create: `packages/Webkul/Product/src/Validator/AssociationValidator.php` (mirror `Webkul\Category\Validator\Catalog\CategoryValidator` + its base `FieldValidator`)
- Test: `packages/Webkul/Product/tests/Feature/AssociationValidatorTest.php`

**Interfaces:**
- Produces `AssociationValidator::validate(int $associationTypeId, array $additionalData, ?int $ignoreId = null): void` (throws `ValidationException` on failure) — builds Laravel rules from the type's active `AssociationTypeField`s (required/type/regex/number/unique) applied to the `additional_data.common.*` / `additional_data.locale_specific.<locale>.*` paths, using `AssociationTypeField::getValidationRules()` (Plan 1). Unknown field codes rejected. Mirror how `CategoryValidator` composes `unknownFieldsValidate` + `inputFieldValidate`.

- [ ] **Step 1: Write failing test** — a type with a required `quantity` field (validation `number`): `validate($typeId, ['common'=>['quantity'=>'abc']])` throws (non-numeric); `['common'=>['quantity'=>'2']]` passes; missing required throws; an unknown field code throws.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — copy `CategoryValidator` + `FieldValidator` structure into `AssociationValidator` (single class is fine if small), pulling fields via `AssociationTypeFieldRepository`/the type's `fields` relation, building rules keyed on the `additional_data` json paths. Reuse Plan 1's `AssociationTypeField::getValidationRules()`/`getJsonPath()`.

- [ ] **Step 4: Run — passes.**

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Validator/AssociationValidator.php packages/Webkul/Product/tests/Feature/AssociationValidatorTest.php
git commit -m "feat(product): association link field-value validator"
```

---

### Task 3: AbstractType::update — parse unified payload, validate, rich sync

**Files:**
- Modify: `packages/Webkul/Product/src/Type/AbstractType.php` (`update()` + `syncAssociationLinks()`)
- Test: `packages/Webkul/Product/tests/Feature/ProductAssociationRichSaveTest.php`

**Interfaces:**
- `update()` accepts, in `$data`, an optional `associations` map: `associations => { <typeCode> => [ { sku, additional_data? }, ... ] }`. When present for a type:
  - For the 3 legacy sections, ALSO write `values['associations'][section] = [skus]` (back-compat JSON) exactly as today (derive the sku list from the unified payload).
  - Validate each link's `additional_data` via `AssociationValidator` for that type; a failure aborts the save with a validation error.
  - Sync the table via `syncTypeWithData` (Task 1) with resolved product ids + additional_data (preserving Plan 2's resilience wrapper).
  - Still accept the LEGACY flat `up_sells[]`/etc. keys when the unified `associations` map is absent (REST/import path) — route them through `syncFromSkuList` as today (now additional_data-preserving).

- [ ] **Step 1: Write failing test** — (a) update a product with `associations => { up_sells: [ {sku: A, additional_data: {common:{quantity:'2'}}} ], bundle_kit: [ {sku: B, additional_data:{common:{quantity:'3'}}} ] }` (seed a custom `bundle_kit` type with a quantity field): assert `values['associations']['up_sells']==[A]` (JSON back-compat), and `product_associations` has an up_sells row (quantity 2) + a bundle_kit row (quantity 3). (b) A second ordinary product save with the LEGACY flat `up_sells[]=[A]` (no additional_data) must NOT wipe the quantity on A (preservation). (c) invalid additional_data (quantity non-numeric) → validation error, nothing persisted.

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — in `update()`, detect `$data['associations']` (unified). Build per-type link lists (resolve sku→id via the repo), validate via `AssociationValidator`, and after `$product->save()` call the rich sync per type. For legacy sections, also set the JSON list. Keep the existing flat-key handling as the fallback. Keep the try/catch resilience wrapper.

- [ ] **Step 4: Run — passes.** Run the FULL Product + AdminApi suites (back-compat regression is the priority).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Product/src/Type/AbstractType.php packages/Webkul/Product/tests/Feature/ProductAssociationRichSaveTest.php
git commit -m "feat(product): rich association save with per-link field values"
```

---

### Task 4: ProductController@edit — provide types + links to the view

**Files:**
- Modify: `packages/Webkul/Admin/src/Http/Controllers/Catalog/ProductController.php` (`edit()`), and `catalog/products/edit.blade.php` include (L246-249)
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/ProductLinksViewTest.php`

**Interfaces:**
- `edit()` passes to the links view: `$associationTypes` = `AssociationTypeRepository::getActiveTypes()` (each with translated name + fields + options), and for the current product, its existing links grouped by type code with resolved product display data (`normalizeWithImage()`) + `additional_data`. Provide a compact JSON structure the Vue component consumes: `[{ code, name, fields:[...], links:[{ sku, name, image, additional_data }] }]`.

- [ ] **Step 1: Write failing test** — `GET` product edit page for a product with a custom-type link returns 200 and the payload includes the custom type + the link's additional_data. (Assert on the rendered data or a controller-provided view var via a view test.)

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Implement** — build the structure in `edit()` (or a small presenter/service to keep the controller thin), replacing the 3 hardcoded `@include` params with `$associationTypes`. Use `getLinksForProduct()` + eager-loaded relations (no N+1).

- [ ] **Step 4: Run — passes.**

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint && vendor/bin/pint --test
git add packages/Webkul/Admin/src/Http/Controllers/Catalog/ProductController.php packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php packages/Webkul/Admin/tests/Feature/Catalog/ProductLinksViewTest.php
git commit -m "feat(admin): supply active association types + links to product edit"
```

---

### Task 5: links.blade — dynamic types + per-link field inputs (component-first)

**Files:**
- Rewrite: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php`
- Create (reusable): `packages/Webkul/Admin/src/Resources/views/components/associations/link-fields.blade.php` — renders one link's custom-field inputs (per the type's fields, `additional_data` bucket), all `x-admin::form.control-group.*`.
- Test: extend `ProductLinksViewTest`.

**Interfaces:**
- `v-product-links` becomes data-driven: `types` = the Task-4 `$associationTypes` payload (dynamic). For each type, list added links; for each link render `<x-admin::associations.link-fields>` (the type's fields bound to that link's `additional_data`). Emit unified hidden inputs: `associations[<typeCode>][<i>][sku]` and `associations[<typeCode>][<i>][additional_data][common|locale_specific][<locale>][<fieldCode>]`. Keep the product-search drawer + add/remove. Preserve `view_render_event` hooks.

- [ ] **Step 1: Build the reusable `link-fields` component** — given a type's `fields` + a link's `additional_data`, render each field via `x-admin::form.control-group` (type/validation/options honored, `value_per_locale` → locale bucket). NO raw controls. Model field rendering on `components/categories/dynamic-fields.blade.php`.

- [ ] **Step 2: Rewrite `links.blade`** — replace hardcoded `types`/`addedProducts` with the dynamic payload; render `<x-admin::associations.link-fields>` per link; change hidden inputs to the unified `associations[...]` names; keep add/remove/search. Component-first throughout.

- [ ] **Step 3: Test** — render assertion: product edit shows a custom type + its field input for an existing link; a feature test that POSTs the unified payload persists the link + additional_data (ties to Task 3). `vendor/bin/pest --filter=ProductLinksViewTest`.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php packages/Webkul/Admin/src/Resources/views/components/associations/link-fields.blade.php packages/Webkul/Admin/tests/Feature/Catalog/ProductLinksViewTest.php
git commit -m "feat(admin): dynamic association types + per-link fields on product edit"
```

---

### Task 6: Translations

**Files:** `packages/Webkul/Admin/src/Resources/lang/en_US/app.php` + all 32 other locales.

- [ ] **Step 1: Add any new keys** used in Tasks 4-5 (e.g. generic add-link / field labels) under the products edit links node in en_US.
- [ ] **Step 2: Propagate to all 32 locales** with natural translations (`:param` intact).
- [ ] **Step 3: `php artisan unopim:translations:check` — green.**
- [ ] **Step 4: Commit** `i18n: product edit association link strings for all locales`.

---

### Task 7: Playwright E2E — rich links on product edit

**Files:** `tests/e2e-pw/tests/catalog/product-association-links.spec.js`

- [ ] **Step 1: Spec** — (precondition: a `bundle_kit` type with a required `quantity` field exists — create via the Plan-1 admin or a fixture). On a product's Links tab: assert `bundle_kit` appears among types; add a product under it; fill `quantity=2`; save; reload; assert the link + quantity=2 persist; then do an unrelated product save and assert quantity STILL 2 (preservation).
- [ ] **Step 2: Run** `cd tests/e2e-pw && npx playwright test tests/catalog/product-association-links.spec.js` — pass.
- [ ] **Step 3: Commit** `test(e2e): rich association links with per-link fields on product edit`.

---

## Self-Review

**Spec coverage:** dynamic types on product edit (Task 4-5); per-link custom fields incl. quantity persisted to additional_data (Task 3, 5); validation (Task 2-3); additional_data preservation across ordinary saves (Task 1, 3); back-compat legacy flat payload + JSON (Task 3); translations (Task 6); E2E incl. the preservation guarantee (Task 7).

**Placeholder scan:** none — each "mirror X" names the exact Category/Plan-1 source to copy.

**Type consistency:** unified payload shape `associations[<typeCode>][<i>][sku|additional_data]` identical across Tasks 3 and 5; `syncTypeWithData` used by Task 3, defined in Task 1; `additional_data` bucket shape consistent with Plan 1.

**Assumptions to verify during execution:** exact `edit()` view-var wiring + whether a thin presenter is warranted (Task 4); how the product-search drawer returns product objects (reuse existing `normalizeWithImage`); confirm the unified payload doesn't collide with any other product-form field named `associations`.
