# Configurable Product Associations — Design Spec

**Date:** 2026-07-10
**Status:** Approved, ready for planning
**Visual:** https://claude.ai/code/artifact/bf2d46c6-9c87-4003-9524-c161d4b86cc0

## Problem

UnoPim ships exactly three association types — `related_products`, `up_sells`,
`cross_sells` — as hardcoded constants in
`packages/Webkul/Product/src/Type/AbstractType.php`. There is no table and no
model: associations are bare SKU arrays stored inside the `products.values`
JSON column under an `associations` key. This means:

1. Merchants cannot create their own association types.
2. A link cannot carry extra data (e.g. a **quantity**, needed for bundles/kits).

## Goal

Two independent axes:

1. **Configurable types** — admins create/edit/delete association types through
   the admin UI, not locked to the three defaults.
2. **Rich link data** — each type defines custom fields (Category-field style);
   every link stores values (quantity, note, priority…). Bundles/kits are just a
   type with a `quantity` field — the generic system, not a special case.

## Locked decisions

| Question | Decision |
|---|---|
| Rich data | Generic custom fields; bundles are one instance of it |
| Type management | Admin UI CRUD, DB-backed (defaults seeded) |
| Field engine | **Category-field style** (not full Attribute system) |
| Storage | Dedicated tables; per-link values inline as `additional_data` JSON on the link row |
| Value scoping | Locale-scoped (`common` + `locale_specific`), **no channel** — same as category fields |
| Back-compat | Full & seamless — migrate data + compat layer; old REST/CSV keep working |
| Targets | Products only, one-directional (stored on source), as today |
| Import/export | Default types keep existing columns; custom types add `{type}` + `{type}:{field}` columns |
| Migration | Migrate all types to tables incl. the 3 defaults; compat layer re-emits old JSON |
| Product-type restriction | Skipped (YAGNI) |

## Cross-cutting constraints (apply to every plan)

- **Laravel 13** best practices (repo already on L13 — commit `43d2e1b7`).
- **Component-first UI:** never write raw form markup; reuse existing `x-admin::*`
  components, or build a reusable component first and consume it. Reuse the
  category `dynamic-fields` component for link values.
- **No custom one-off code:** follow UnoPim standards; reuse existing rules,
  repositories, traits, components. Add new only where no equivalent exists.
- **Extendability:** Concord proxy models only (never concrete refs); field types
  in mergeable config; fire before/after events around every mutation so plugins
  hook without patching core.
- **Performance:** indexes on all FK/lookup/status/position columns; eager-load to
  avoid N+1; parameterized DataGrid queries.
- **Security:** ACL via `bouncer()` on every action; type-hinted FormRequests (no
  inline validation); `$fillable` mass-assignment guards; escape output; protect
  `is_user_defined = 0` defaults server-side.
- **Backward compatibility:** preserve behavior; where impossible, ship an
  upgrade migration from the old JSON shape to the new tables (Plan 2).

## Field engine — why Category-field, not Attributes

UnoPim has two field systems: the full **Attribute** system (channel+locale
scoping, families, price type) and the lighter **Category-field** system (own
definition tables, values inline as JSON `common`/`locale_specific`, locale-only).
Association link metadata (quantity, note) is light and does not need channel
scoping or families, and the user explicitly wants it "like the categories
field." So we mirror `CategoryField` for definitions and store per-link values as
`additional_data` JSON — but on the dedicated association row, keeping links
relational/queryable while custom data rides along.

`quantity` = a text field with `validation: number` (Category fields have no
dedicated number type; number/decimal is a validation rule — reuse that).

## Data model — 7 new tables

### Type definition
- **`association_types`** — `id`, `code` (unique), `status`, `position`, `is_user_defined`
- **`association_type_translations`** — `association_type_id`, `locale`, `name`

### Custom fields (mirror `CategoryField` 1:1)
- **`association_type_fields`** — `id`, `association_type_id` (FK), `code`, `type`,
  `validation`, `regex_pattern`, `is_required`, `is_unique`, `position`,
  `value_per_locale`, `enable_wysiwyg`, `section`
- **`association_type_field_translations`** — `field_id`, `locale`, `name`
- **`association_type_field_options`** — `id`, `field_id`, `code`, `sort_order`
- **`association_type_field_option_translations`** — `option_id`, `locale`, `label`

### Links
- **`product_associations`** — `id`, `product_id` (source), `association_type_id`,
  `related_product_id` (target), `position`, `additional_data` (JSON:
  `common` + `locale_specific`)

Field types match Category exactly: text, textarea, boolean, select, multiselect,
date, datetime, image, file, checkbox. Same `FieldTypes` / `ValidationTypes` /
`FieldOption` / `NotSupportedFields` rule set and option/translation structure.

### Storage shape example
```jsonc
// one product_associations row (bundle component)
{
  "product_id": 10,            // source
  "association_type_id": 4,    // bundle_kit
  "related_product_id": 55,    // target (SKU-RAM-16)
  "position": 1,
  "additional_data": {
    "common": { "quantity": "2" }
  }
}
```

## Back-compat & migration

1. **Seed defaults** — migration inserts `related_products`, `up_sells`,
   `cross_sells` as rows with `is_user_defined = false` and their exact original
   codes, zero custom fields. Behaviour identical to today.
2. **Backfill** — one-off migration reads every product's
   `values.associations.{key}` arrays → inserts `product_associations` rows
   (SKU→id, empty `additional_data`).
3. **Compat layer** — a service that presents/accepts the old JSON shape
   (`values.associations.{code} = [sku]`) on top of the new tables, so REST,
   CSV, and the normalizer keep working unchanged.
4. **Cutover** — tables become source of truth; old JSON retained as safety, then
   retired.

## Layers that change

| Layer | Change |
|---|---|
| `AbstractType` constants | Kept as fallback; type list read from `AssociationTypeRepository` |
| `update()` / `ValueSetter` | Write links to `product_associations` via new repository |
| Validator | New `AssociationValidator` builds rules from each type's fields (mirrors `CategoryValidator`) |
| Mapper / Normalizer | Read links from table; compat layer re-emits old JSON shape |
| `links.blade.php` | Loop dynamic active types + `dynamic-fields` per link |
| AdminApi `ProductController` | Accept both old and new payload shapes |
| DataTransfer import/export | Default types keep columns; custom types add `{type}` + `{type}:{field}` columns |
| AI tool `ManageAssociations` | Resolve types dynamically; support field values |

## New code (Category-field clone, scoped per type)

- **Models / Repositories** — `AssociationType`, `AssociationTypeField(+Option)`,
  `ProductAssociation` + repositories.
- **Admin** — controller, DataGrid, `index/create/edit` blades, menu, ACL, routes.
- **Config + rules** — `association_field_types` config; `FieldTypes`,
  `ValidationTypes`, `FieldOption`, `NotSupportedFields` rules.
- **Service** — `AssociationAdditionalDataMapper` (+ facade).
- **Compat** — old-JSON ↔ link-rows translator for REST/CSV/normalizer.
- **DataTransfer** — importer/exporter/storage/job-validators for both type
  definitions and link values.

## Verification

**Pest** — type CRUD; field CRUD + options; link save with field values;
required/unique/number validation; migration backfill (no loss); REST accepts old
+ new shape; import/export round-trip incl. custom fields.

**Playwright** — create custom type + quantity field; add bundle links with
quantities on a product; reload persists; default 3 types still edit as before.

**Gates** — `vendor/bin/pint --test`; `php artisan unopim:translations:check`
across all 33 locales (all new UI strings translated).

## Open items to settle during planning

- Exact reference tables/models to copy from `packages/Webkul/Category` (verified
  in exploration; re-confirm line numbers during implementation).
- Which package hosts the new code: extend `packages/Webkul/Product` (links +
  types live with the product domain) vs a dedicated package. Leaning Product for
  cohesion with the existing association logic.
